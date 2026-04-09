package com.tenantpro.app

import android.Manifest
import android.content.pm.PackageManager
import android.content.res.ColorStateList
import android.net.Uri
import android.os.Build
import android.os.Bundle
import android.widget.ImageView
import android.widget.TextView
import androidx.activity.result.contract.ActivityResultContracts
import androidx.activity.viewModels
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import androidx.core.splashscreen.SplashScreen.Companion.installSplashScreen
import androidx.drawerlayout.widget.DrawerLayout
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.lifecycleScope
import androidx.lifecycle.repeatOnLifecycle
import androidx.navigation.NavController
import androidx.navigation.fragment.NavHostFragment
import androidx.navigation.ui.AppBarConfiguration
import androidx.navigation.ui.navigateUp
import androidx.navigation.ui.setupActionBarWithNavController
import androidx.navigation.ui.setupWithNavController
import com.google.android.material.appbar.MaterialToolbar
import com.google.android.material.navigation.NavigationView
import com.tenantpro.app.databinding.ActivityMainBinding
import com.tenantpro.app.ui.auth.LoginViewModel
import com.tenantpro.app.utils.DataStoreManager
import com.tenantpro.app.utils.toast
import dagger.hilt.android.AndroidEntryPoint
import javax.inject.Inject
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.launch

@AndroidEntryPoint
class MainActivity : AppCompatActivity() {

    private lateinit var binding: ActivityMainBinding
    private lateinit var navController: NavController
    private lateinit var appBarConfiguration: AppBarConfiguration
    private val loginViewModel: LoginViewModel by viewModels()

    @Inject
    lateinit var dataStoreManager: DataStoreManager

    private val requestNotificationPermission =
        registerForActivityResult(ActivityResultContracts.RequestPermission()) { }

    override fun onCreate(savedInstanceState: Bundle?) {
        // Keep splash visible while we check the stored token
        val splashScreen = installSplashScreen()
        splashScreen.setKeepOnScreenCondition { loginViewModel.isCheckingToken.value }

        super.onCreate(savedInstanceState)
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)
        requestNotificationsIfNeeded()

        // Keep focused fields visible when keyboard opens on any fragment screen.
        ViewCompat.setOnApplyWindowInsetsListener(binding.navHostFragment) { view, insets ->
            val systemBarsBottom = insets.getInsets(WindowInsetsCompat.Type.systemBars()).bottom
            val imeBottom = insets.getInsets(WindowInsetsCompat.Type.ime()).bottom
            val targetBottom = maxOf(systemBarsBottom, imeBottom)

            view.setPadding(
                view.paddingLeft,
                view.paddingTop,
                view.paddingRight,
                targetBottom
            )
            insets
        }

        val navHostFragment =
            supportFragmentManager.findFragmentById(R.id.nav_host_fragment) as NavHostFragment
        navController = navHostFragment.navController

        // Configure drawer and app bar
        val drawerLayout: DrawerLayout = binding.drawerLayout
        val navView: NavigationView = binding.navigationView
        val toolbar: MaterialToolbar = binding.toolbar

        // Top-level destinations (drawer menu items)
        appBarConfiguration = AppBarConfiguration(
            setOf(
                R.id.homeFragment,
                R.id.invoicesFragment,
                R.id.rentalInfoFragment,
                R.id.notificationsFragment,
                R.id.maintenanceFragment,
                R.id.queriesFragment,
                R.id.accountSettingsFragment,
                R.id.loginFragment
            ),
            drawerLayout
        )

        setSupportActionBar(toolbar)
        setupActionBarWithNavController(navController, appBarConfiguration)
        val toolbarIconColor = ContextCompat.getColor(this, R.color.on_primary)
        toolbar.setTitleTextColor(toolbarIconColor)
        toolbar.navigationIcon?.setTint(toolbarIconColor)
        navView.setupWithNavController(navController)

        bindDrawerHeader(navView)

        // Handle logout from drawer menu
        navView.setNavigationItemSelectedListener { menuItem ->
            when (menuItem.itemId) {
                R.id.logoutItem -> {
                    lifecycleScope.launch {
                        loginViewModel.logout()
                        navController.navigate(
                            R.id.loginFragment,
                            null,
                            androidx.navigation.NavOptions.Builder()
                                .setPopUpTo(R.id.homeFragment, true)
                                .build()
                        )
                        toast("Logged out successfully")
                    }
                    drawerLayout.close()
                    true
                }
                else -> {
                    navView.setCheckedItem(menuItem.itemId)
                    navController.navigate(menuItem.itemId)
                    drawerLayout.closeDrawer(navView)
                    true
                }
            }
        }

        // Hide toolbar for login/register screens
        navController.addOnDestinationChangedListener { _, destination, _ ->
            val shouldShowToolbar = destination.id != R.id.loginFragment && destination.id != R.id.registerFragment
            binding.appBarLayout.visibility = if (shouldShowToolbar) android.view.View.VISIBLE else android.view.View.GONE
            toolbar.setTitleTextColor(toolbarIconColor)
            toolbar.navigationIcon?.setTint(toolbarIconColor)

            // Update toolbar title
            when (destination.id) {
                R.id.homeFragment            -> toolbar.title = getString(R.string.label_home)
                R.id.invoicesFragment        -> toolbar.title = getString(R.string.label_invoices)
                R.id.rentalInfoFragment      -> toolbar.title = getString(R.string.label_my_rental)
                R.id.notificationsFragment   -> toolbar.title = getString(R.string.label_notifications)
                R.id.maintenanceFragment     -> toolbar.title = getString(R.string.label_maintenance)
                R.id.queriesFragment         -> toolbar.title = getString(R.string.label_queries)
                R.id.accountSettingsFragment -> toolbar.title = getString(R.string.label_account_settings)
            }
        }

        // Set the start destination ONCE based on the actual persisted auth state.
        // Use the repository-backed flow rather than the StateFlow default value.
        if (savedInstanceState == null) {
            lifecycleScope.launch {
                val loggedIn = loginViewModel.hasSavedSession()
                val graph = navController.navInflater.inflate(R.navigation.nav_graph)
                graph.setStartDestination(
                    if (loggedIn) R.id.homeFragment else R.id.loginFragment
                )
                navController.graph = graph
            }
        }
    }

    override fun onSupportNavigateUp(): Boolean {
        return navController.navigateUp(appBarConfiguration) || super.onSupportNavigateUp()
    }

    private fun requestNotificationsIfNeeded() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU &&
            ContextCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED
        ) {
            requestNotificationPermission.launch(Manifest.permission.POST_NOTIFICATIONS)
        }
    }

    private fun bindDrawerHeader(navView: NavigationView) {
        val header = navView.getHeaderView(0)
        val avatar = header.findViewById<ImageView>(R.id.userAvatar)
        val userName = header.findViewById<TextView>(R.id.userName)
        val userEmail = header.findViewById<TextView>(R.id.userEmail)

        lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                combine(
                    dataStoreManager.userName,
                    dataStoreManager.userEmail,
                    dataStoreManager.phoneNumber,
                    dataStoreManager.profileImageUri
                ) { name, email, phone, imageUri ->
                    HeaderUi(
                        name = name?.takeIf { it.isNotBlank() } ?: getString(R.string.user_profile),
                        email = email?.takeIf { it.isNotBlank() }
                            ?: phone?.takeIf { it.isNotBlank() }
                            ?: getString(R.string.profile_no_contact),
                        imageUri = imageUri
                    )
                }.collect { ui ->
                    userName.text = ui.name
                    userEmail.text = ui.email

                    if (!ui.imageUri.isNullOrBlank()) {
                        avatar.setImageURI(Uri.parse(ui.imageUri))
                        avatar.imageTintList = null
                    } else {
                        avatar.setImageResource(R.drawable.ic_account_circle)
                        avatar.imageTintList = ColorStateList.valueOf(getColor(R.color.secondary))
                    }
                }
            }
        }
    }
}

private data class HeaderUi(
    val name: String,
    val email: String,
    val imageUri: String?
)


