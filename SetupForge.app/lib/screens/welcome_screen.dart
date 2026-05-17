import 'package:flutter/material.dart';

class WelcomeScreen extends StatelessWidget {
  const WelcomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Spacer(),

              Container(
                height: 78,
                width: 78,
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(20),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.05),
                      blurRadius: 12,
                      offset: const Offset(0, 6),
                    ),
                  ],
                ),
                padding: const EdgeInsets.all(12),
                child: Image.asset('assets/logo.png', fit: BoxFit.contain),
              ),

              const SizedBox(height: 28),

              Text(
                'Welcome to SetupForge',
                style: theme.textTheme.headlineMedium?.copyWith(
                  fontWeight: FontWeight.w800,
                ),
              ),

              const SizedBox(height: 12),

              Text(
                'Plan your business setup faster with a guided mobile experience built around your needs.',
                style: theme.textTheme.bodyLarge?.copyWith(
                  height: 1.5,
                  color: Colors.black87,
                ),
              ),

              const SizedBox(height: 28),

              Container(
                padding: const EdgeInsets.all(18),
                decoration: BoxDecoration(
                  color: const Color(0xFFF7F9FC),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: const Color(0xFFE4EAF3)),
                ),
                child: Column(
                  children: const [
                    _WelcomePoint(
                      icon: Icons.check_circle_outline_rounded,
                      text:
                          'Get recommendations based on your business type and budget',
                    ),
                    SizedBox(height: 14),
                    _WelcomePoint(
                      icon: Icons.check_circle_outline_rounded,
                      text: 'Save your setup progress and continue anytime',
                    ),
                    SizedBox(height: 14),
                    _WelcomePoint(
                      icon: Icons.check_circle_outline_rounded,
                      text: 'Review packages and place orders from your phone',
                    ),
                  ],
                ),
              ),

              const Spacer(),

              SizedBox(
                width: double.infinity,
                height: 56,
                child: ElevatedButton(
                  onPressed: () {
                    Navigator.pushNamed(context, '/setup');
                  },
                  child: const Text('Start My Setup'),
                ),
              ),

              const SizedBox(height: 12),

              SizedBox(
                width: double.infinity,
                height: 56,
                child: OutlinedButton(
                  onPressed: () {
                    Navigator.pushNamed(context, '/login');
                  },
                  child: const Text('I Already Have an Account'),
                ),
              ),

              const SizedBox(height: 12),

              Center(
                child: TextButton(
                  onPressed: () {
                    Navigator.pushNamed(context, '/signup');
                  },
                  child: const Text('Create New Account'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _WelcomePoint extends StatelessWidget {
  final IconData icon;
  final String text;

  const _WelcomePoint({required this.icon, required this.text});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 22, color: theme.colorScheme.primary),
        const SizedBox(width: 12),
        Expanded(
          child: Text(
            text,
            style: theme.textTheme.bodyMedium?.copyWith(
              height: 1.45,
              fontWeight: FontWeight.w500,
            ),
          ),
        ),
      ],
    );
  }
}
