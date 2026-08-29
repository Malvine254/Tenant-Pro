package com.tenantpro.app

import android.Manifest
import android.app.KeyguardManager
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.content.res.ColorStateList
import android.os.Build
import android.os.Bundle
import android.view.View
import android.widget.ImageView
import android.widget.TextView
import androidx.activity.result.contract.ActivityResultContracts
import androidx.activity.viewModels
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import androidx.core.os.bundleOf
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
import com.bumptech.glide.Glide
import com.google.android.material.appbar.MaterialToolbar
import com.google.android.material.navigation.NavigationView
import com.tenantpro.app.databinding.ActivityMainBinding
import com.tenantpro.app.ui.auth.LoginViewModel
import com.tenantpro.app.ui.queries.QueriesFragment
import com.tenantpro.app.utils.DataStoreManager
import com.tenantpro.app.utils.SessionManager
import com.tenantpro.app.utils.toAbsoluteAssetUrl
import com.tenantpro.app.utils.toast
import dagger.hilt.android.AndroidEntryPoint
import javax.inject.Inject
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.launch

@AndroidEntryPoint
class MainActivity : AppCompatActivity() {

    private lateinit var binding: ActivityMainBinding
    private lateinit var navController: NavController
    private lateinit var appBarConfiguration: AppBarConfiguration
    private val loginViewModel: LoginViewModel by viewModels()
    private var pendingInvitationCode: String? = null
    private var pendingNotificationDestination: String? = null
    private var pendingNotificationEntityId: String? = null
    private var appUnlockedForSession = false
    private var unlockPromptInProgress = false

    @Inject
    lateinit var dataStoreManager: DataStoreManager

    @Inject
    lateinit var sessionManager: SessionManager

    private val requestNotificationPermission =
        registerForActivityResult(ActivityResultContracts.RequestPermission()) { granted ->
            if (granted) {
                lifecycleScope.launch {
                    syncFcmTokenIfLoggedIn()
                }
            }
        }

    private val deviceCredentialLauncher =
        registerForActivityResult(ActivityResultContracts.StartActivityForResult()) { result ->
            unlockPromptInProgress = false
            appUnlockedForSession = result.resultCode == RESULT_OK
            if (!appUnlockedForSession) {
                toast("Unlock required to continue")
                if (::navController.isInitialized) {
                    navController.navigate(
                        R.id.loginFragment,
                        null,
                        androidx.navigation.NavOptions.Builder()
                            .setPopUpTo(R.id.nav_graph, true)
                            .build()
                    )
                }
            }
        }

    override fun onCreate(savedInstanceState: Bundle?) {
        // Keep splash visible while we check the stored token
        val splashScreen = installSplashScreen()
        splashScreen.setKeepOnScreenCondition { loginViewModel.isCheckingToken.value }

        super.onCreate(savedInstanceState)
        pendingInvitationCode = extractInvitationCode(intent)
        readNotificationDestination(intent)
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)
        ensureNotificationPermission()

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
                R.id.paymentHistoryFragment,
                R.id.queriesFragment,
                R.id.accountSettingsFragment,
                R.id.loginFragment,
                R.id.emailVerificationFragment
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
                                .setPopUpTo(R.id.nav_graph, true)
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

        // Hide toolbar for login/register screens and show a left-aligned title for main screens.
        navController.addOnDestinationChangedListener { _, destination, _ ->
            val shouldShowToolbar = destination.id != R.id.loginFragment
                && destination.id != R.id.registerFragment
                && destination.id != R.id.emailVerificationFragment
            binding.appBarLayout.visibility = if (shouldShowToolbar) android.view.View.VISIBLE else android.view.View.GONE
            val title = when (destination.id) {
                R.id.homeFragment -> "Home"
                R.id.invoicesFragment -> "Invoices"
                R.id.rentalInfoFragment -> "Rental Info"
                R.id.notificationsFragment -> "Updates"
                R.id.maintenanceFragment -> "Maintenance"
                R.id.queriesFragment -> "Chats"
                R.id.accountSettingsFragment -> "Account"
                R.id.paymentFragment -> "Make Payment"
                R.id.paymentHistoryFragment -> "Payment History"
                else -> ""
            }
            supportActionBar?.title = title
            toolbar.title = title
            toolbar.subtitle = ""
            toolbar.setTitleTextColor(toolbarIconColor)
            toolbar.navigationIcon?.setTint(toolbarIconColor)
            handlePendingInvitationDeepLink()
            handlePendingNotificationNavigation()
        }

        // Set the start destination ONCE based on the actual persisted auth state.
        // Use the repository-backed flow rather than the StateFlow default value.
        if (savedInstanceState == null) {
            // The graph's XML default is Login. Keep the host hidden until the
            // persisted session decides the real start destination, avoiding a login flash.
            binding.navHostFragment.visibility = View.INVISIBLE
            lifecycleScope.launch {
                val loggedIn = loginViewModel.hasSavedSession()
                val biometricLockEnabled = dataStoreManager.biometricLockEnabled.firstOrNull() ?: false
                val graph = navController.navInflater.inflate(R.navigation.nav_graph)
                graph.setStartDestination(
                    if (loggedIn && !biometricLockEnabled) R.id.homeFragment else R.id.loginFragment
                )
                navController.graph = graph
                binding.navHostFragment.visibility = View.VISIBLE
                handlePendingInvitationDeepLink()
                handlePendingNotificationNavigation()
                syncFcmTokenIfLoggedIn()
            }
        } else {
            // Android restored the correct destination; reveal it without
            // rebuilding the graph or briefly displaying Login.
            binding.navHostFragment.visibility = View.VISIBLE
        }

        // Navigate to login when the session expires (401 received on any request)
        lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                sessionManager.sessionExpired.collect { message ->
                    toast(message)
                    navController.navigate(
                        R.id.loginFragment,
                        null,
                        androidx.navigation.NavOptions.Builder()
                            .setPopUpTo(R.id.nav_graph, true)
                            .build()
                    )
                }
            }
        }
    }

    override fun onStart() {
        super.onStart()
    }

    override fun onResume() {
        super.onResume()
        maybePromptAppUnlock()
    }

    override fun onNewIntent(intent: Intent) {
        super.onNewIntent(intent)
        setIntent(intent)
        pendingInvitationCode = extractInvitationCode(intent)
        readNotificationDestination(intent)
        handlePendingInvitationDeepLink()
        handlePendingNotificationNavigation()
    }

    override fun onStop() {
        super.onStop()
    }

    override fun onSupportNavigateUp(): Boolean {
        return navController.navigateUp(appBarConfiguration) || super.onSupportNavigateUp()
    }

    fun markAppUnlockedForSession() {
        appUnlockedForSession = true
    }

    fun ensureNotificationPermission() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU &&
            ContextCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED
        ) {
            requestNotificationPermission.launch(Manifest.permission.POST_NOTIFICATIONS)
        }
    }

    private fun maybePromptAppUnlock() {
        if (unlockPromptInProgress || appUnlockedForSession || !::navController.isInitialized) return

        val destinationId = navController.currentDestination?.id
        if (destinationId == R.id.loginFragment ||
            destinationId == R.id.registerFragment ||
            destinationId == R.id.emailVerificationFragment
        ) return

        lifecycleScope.launch {
            val biometricLockEnabled = dataStoreManager.biometricLockEnabled.firstOrNull() ?: false
            if (!biometricLockEnabled || appUnlockedForSession || unlockPromptInProgress) return@launch
            if (!loginViewModel.hasSavedSession()) return@launch

            val keyguardManager = getSystemService(Context.KEYGUARD_SERVICE) as KeyguardManager
            if (!keyguardManager.isDeviceSecure) {
                dataStoreManager.saveBiometricLockEnabled(false)
                toast("Set a phone screen lock first to use biometric lock")
                return@launch
            }

            val unlockIntent = keyguardManager.createConfirmDeviceCredentialIntent(
                "Unlock Tenant Pro",
                "Confirm your device lock to continue"
            ) ?: return@launch

            unlockPromptInProgress = true
            deviceCredentialLauncher.launch(unlockIntent)
        }
    }

    private suspend fun syncFcmTokenIfLoggedIn() {
        if (!loginViewModel.hasSavedSession()) return
        loginViewModel.syncFcmToken()
    }

    private fun extractInvitationCode(intent: Intent?): String? {
        val uri = intent?.data ?: return intent?.getStringExtra("invitation_code")
        val code = when {
            uri.scheme == "tenantpro" && uri.host == "invite" ->
                uri.getQueryParameter("code") ?: uri.lastPathSegment
            uri.scheme == "https" && uri.host.equals("app.starmaxltd.com", ignoreCase = true)
                && uri.pathSegments.firstOrNull() == "invite" ->
                uri.getQueryParameter("code") ?: uri.pathSegments.getOrNull(1)
            else -> null
        }
        return code?.takeIf { it.isNotBlank() }
    }

    private fun handlePendingInvitationDeepLink() {
        val code = pendingInvitationCode ?: return
        if (!::navController.isInitialized || navController.graph.findNode(R.id.rentalInfoFragment) == null) return

        if (navController.currentDestination?.id in setOf(
                R.id.loginFragment,
                R.id.registerFragment,
                R.id.emailVerificationFragment
            )) return

        pendingInvitationCode = null
        navController.navigate(R.id.rentalInfoFragment, bundleOf("invitationCode" to code))
    }

    private fun readNotificationDestination(intent: Intent?) {
        pendingNotificationDestination = intent?.getStringExtra(EXTRA_NOTIFICATION_DESTINATION)
            ?.takeIf { it.isNotBlank() }
            ?: intent?.getStringExtra("destination")?.takeIf { it.isNotBlank() }
            ?: intent?.getStringExtra("notification_destination")?.takeIf { it.isNotBlank() }
        pendingNotificationEntityId = intent?.getStringExtra(EXTRA_NOTIFICATION_ENTITY_ID)
            ?.takeIf { it.isNotBlank() }
            ?: intent?.getStringExtra("conversation_id")?.takeIf { it.isNotBlank() }
            ?: intent?.getStringExtra("invoice_id")?.takeIf { it.isNotBlank() }
            ?: intent?.getStringExtra("payment_id")?.takeIf { it.isNotBlank() }
            ?: intent?.getStringExtra("maintenance_request_id")?.takeIf { it.isNotBlank() }
    }

    private fun handlePendingNotificationNavigation() {
        val destination = pendingNotificationDestination ?: return
        if (!::navController.isInitialized) return
        if (navController.currentDestination?.id in setOf(
                R.id.loginFragment,
                R.id.registerFragment,
                R.id.emailVerificationFragment
            )) return

        val destinationId = when (destination.uppercase()) {
            "CHAT" -> R.id.queriesFragment
            "INVOICES" -> R.id.invoicesFragment
            "PAYMENTS" -> R.id.paymentHistoryFragment
            "MAINTENANCE" -> R.id.queriesFragment
            "RENTAL" -> R.id.rentalInfoFragment
            else -> R.id.notificationsFragment
        }
        if (navController.graph.findNode(destinationId) == null) return

        val entityId = pendingNotificationEntityId
        val args = entityId?.let {
            if (destination.equals("CHAT", ignoreCase = true)) {
                bundleOf("conversationId" to it)
            } else {
                bundleOf("notificationEntityId" to it)
            }
        }
        pendingNotificationDestination = null
        pendingNotificationEntityId = null

        if (navController.currentDestination?.id == destinationId) {
            if (destinationId == R.id.queriesFragment && entityId != null) {
                val navHost = supportFragmentManager.findFragmentById(R.id.nav_host_fragment) as? NavHostFragment
                (navHost?.childFragmentManager?.primaryNavigationFragment as? QueriesFragment)
                    ?.openConversation(entityId)
            }
            return
        }

        navController.navigate(destinationId, args)
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

                    val loaded = if (!ui.imageUri.isNullOrBlank()) {
                        runCatching {
                            Glide.with(this@MainActivity)
                                .load(ui.imageUri.toAbsoluteAssetUrl())
                                .placeholder(R.drawable.ic_account_circle)
                                .error(R.drawable.ic_account_circle)
                                .into(avatar)
                            avatar.imageTintList = null
                        }.isSuccess
                    } else false

                    if (!loaded) {
                        Glide.with(this@MainActivity).clear(avatar)
                        avatar.setImageResource(R.drawable.ic_account_circle)
                        avatar.imageTintList = ColorStateList.valueOf(getColor(R.color.primary))
                    }
                }
            }
        }
    }

    companion object {
        const val EXTRA_NOTIFICATION_DESTINATION = "notification_destination"
        const val EXTRA_NOTIFICATION_ENTITY_ID = "notification_entity_id"
    }
}

private data class HeaderUi(
    val name: String,
    val email: String,
    val imageUri: String?
)


