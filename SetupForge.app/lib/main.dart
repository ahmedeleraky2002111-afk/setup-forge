import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'app/app_theme.dart';
import 'screens/app_shell.dart';
import 'screens/auth_gate.dart';
import 'screens/login_screen.dart';
import 'screens/order_summary_screen.dart';
import 'screens/packages_screen.dart';
import 'screens/place_order_screen.dart';
import 'screens/profile_screen.dart';
import 'screens/setup_screen.dart';
import 'screens/signup_screen.dart';
import 'screens/splash_screen.dart';
import 'screens/success_screen.dart';
import 'screens/home_screen.dart';
import 'state/wizard_state.dart';
import 'screens/welcome_screen.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const SetupForgeApp());
}

class SetupForgeApp extends StatelessWidget {
  const SetupForgeApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (_) => WizardState(),
      child: MaterialApp(
        debugShowCheckedModeBanner: false,
        title: 'SetupForge',
        theme: AppTheme.lightTheme,
        initialRoute: '/splash',
        routes: {
          '/splash': (_) => SplashScreen(),
          '/auth-gate': (_) => AuthGate(),
          '/login': (_) => LoginScreen(),
          '/signup': (_) => SignupScreen(),
          '/app-shell': (_) => AppShell(initialIndex: 0),
          '/home': (_) => HomeScreen(),
          '/setup': (_) => SetupScreen(),
          '/packages': (_) => PackagesScreen(),
          '/place-order': (_) => PlaceOrderScreen(),
          '/order-summary': (_) => OrderSummaryScreen(),
          '/success': (_) => SuccessScreen(),
          '/profile': (_) => ProfileScreen(),
          '/welcome': (_) => WelcomeScreen(),
        },
      ),
    );
  }
}
