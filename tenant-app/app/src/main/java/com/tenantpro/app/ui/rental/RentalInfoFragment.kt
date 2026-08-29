package com.tenantpro.app.ui.rental

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ImageView
import android.widget.LinearLayout
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.lifecycleScope
import androidx.lifecycle.repeatOnLifecycle
import com.bumptech.glide.Glide
import com.google.android.material.textview.MaterialTextView
import com.tenantpro.app.R
import com.tenantpro.app.databinding.FragmentRentalInfoBinding
import com.tenantpro.app.utils.showErrorSnackbar
import com.tenantpro.app.utils.showSuccessSnackbar
import com.tenantpro.app.utils.toAbsoluteAssetUrl
import com.tenantpro.app.utils.toKes
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.launch

@AndroidEntryPoint
class RentalInfoFragment : Fragment() {

    companion object {
        private const val FALLBACK_APARTMENT_IMAGE =
            "https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1200&q=80"
    }

    private var _binding: FragmentRentalInfoBinding? = null
    private val binding get() = _binding!!
    private val viewModel: RentalInfoViewModel by viewModels()

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentRentalInfoBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        val invitationCode = arguments?.getString("invitationCode")?.trim().orEmpty()
        if (invitationCode.isNotBlank() && savedInstanceState == null) {
            viewModel.acceptInvitation(invitationCode)
        }

        viewLifecycleOwner.lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                launch {
                    viewModel.uiState.collect { state ->
                        binding.progressRental.visibility =
                            if (state.loading) View.VISIBLE else View.GONE
                        binding.tvOutstandingValue.text = state.outstandingText
                        binding.tvRentalUnitsValue.text = state.units.size.toString()
                        binding.tvRentalPortfolioMeta.text = portfolioMeta(state.units)
                        binding.tvPendingValue.text = state.pendingCount.toString()
                        binding.tvOverdueValue.text = state.overdueCount.toString()
                        bindUnits(state.units)
                    }
                }
                launch {
                    viewModel.events.collect { msg ->
                        if (msg.contains("success", ignoreCase = true) ||
                            msg.contains("accepted", ignoreCase = true)
                        ) {
                            showSuccessSnackbar(msg)
                        } else {
                            showErrorSnackbar(msg)
                        }
                    }
                }
            }
        }
    }

    private fun bindUnits(units: List<RentalUnitItem>) {
        binding.llRentalUnits.removeAllViews()

        if (units.isEmpty()) {
            binding.llRentalEmpty.visibility = View.VISIBLE
            return
        }

        binding.llRentalEmpty.visibility = View.GONE
        units.groupBy { it.propertyName }.forEach { (propertyName, propertyUnits) ->
            val group = layoutInflater.inflate(
                R.layout.item_rental_property_group,
                binding.llRentalUnits,
                false
            )
            group.findViewById<MaterialTextView>(R.id.tvRentalPropertyName).text = propertyName
            group.findViewById<MaterialTextView>(R.id.tvRentalPropertyMeta).text =
                propertyUnits.firstOrNull()?.address?.takeIf { value ->
                    value.isNotBlank() && value.any { it.isLetterOrDigit() }
                } ?: "Address not set"
            group.findViewById<MaterialTextView>(R.id.tvRentalPropertyCount).text =
                "${propertyUnits.size} room${if (propertyUnits.size == 1) "" else "s"}"

            val apartmentImageView = group.findViewById<ImageView>(R.id.ivRentalPropertyImage)
            val apartmentImage = propertyUnits
                .firstOrNull { !it.apartmentImageUrl.isNullOrBlank() }
                ?.apartmentImageUrl
                ?.toAbsoluteAssetUrl()
                ?: FALLBACK_APARTMENT_IMAGE
            Glide.with(this)
                .load(apartmentImage)
                .centerCrop()
                .into(apartmentImageView)

            val unitContainer = group.findViewById<LinearLayout>(R.id.llRentalPropertyUnits)
            propertyUnits.forEach { item ->
                val card = layoutInflater.inflate(
                    R.layout.item_rental_unit,
                    unitContainer,
                    false
                )
                bindUnitCard(card, item)
                unitContainer.addView(card)
            }
            binding.llRentalUnits.addView(group)
        }
    }

    private fun bindUnitCard(card: View, item: RentalUnitItem) {
        val unitImage = item.apartmentImageUrl?.toAbsoluteAssetUrl() ?: FALLBACK_APARTMENT_IMAGE
        Glide.with(this)
            .load(unitImage)
            .centerCrop()
            .into(card.findViewById(R.id.ivUnitPhoto))

        card.findViewById<MaterialTextView>(R.id.tvUnitPropertyName).text = item.propertyName
        card.findViewById<MaterialTextView>(R.id.tvUnitNumber).text = "Unit ${item.unitNumber}"
        card.findViewById<MaterialTextView>(R.id.tvUnitFloor).text = item.floor ?: "-"
        card.findViewById<MaterialTextView>(R.id.tvUnitRent).text = item.rentAmountText ?: "-"
        card.findViewById<MaterialTextView>(R.id.tvUnitMoveIn).text = item.moveInDate
        card.findViewById<MaterialTextView>(R.id.tvUnitAddress).text = item.address
    }

    private fun portfolioMeta(units: List<RentalUnitItem>): String {
        if (units.isEmpty()) return "No active rooms"
        val properties = units.map { it.propertyName }.distinct().size
        val totalRent = units.sumOf { unit ->
            unit.rentAmountText
                ?.replace(Regex("[^0-9.]"), "")
                ?.toDoubleOrNull()
                ?: 0.0
        }
        val roomText = "${units.size} active room${if (units.size == 1) "" else "s"}"
        val propertyText = "$properties propert${if (properties == 1) "y" else "ies"}"
        val rentText = if (totalRent > 0.0) " - ${totalRent.toKes()} monthly" else ""
        return "$roomText across $propertyText$rentText"
    }

    override fun onResume() {
        super.onResume()
        viewModel.refreshRentalInfo()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
