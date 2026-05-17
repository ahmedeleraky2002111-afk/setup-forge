import 'package:flutter/material.dart';
import '../services/api_service.dart';
import 'app_shell.dart';

class SignupScreen extends StatefulWidget {
  const SignupScreen({super.key});

  @override
  State<SignupScreen> createState() => _SignupScreenState();
}

class _SignupScreenState extends State<SignupScreen> {
  static const Color sfBlue = Color(0xFF004CAC);
  static const Color sfBg = Color(0xFFF5F7FB);
  static const Color sfText = Color(0xFF121212);
  static const Color sfMuted = Color(0xFF6C757D);

  final api = ApiService();

  final nameC = TextEditingController();
  final emailC = TextEditingController();
  final phoneC = TextEditingController();
  final passC = TextEditingController();

  bool loading = false;
  bool obscurePass = true;

  Future<void> _signup() async {
    FocusScope.of(context).unfocus();
    setState(() => loading = true);

    try {
      final res = await api.signupFull(
        name: nameC.text.trim(),
        email: emailC.text.trim(),
        phone: phoneC.text.trim(),
        password: passC.text,
      );

      if (!mounted) return;

      if (res["ok"] == true) {
        Navigator.pushAndRemoveUntil(
          context,
          MaterialPageRoute(builder: (_) => const AppShell(initialIndex: 0)),
          (route) => false,
        );
      } else {
        final msg = (res["error"] ?? "Signup failed").toString();
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(msg)));
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text("Error: $e")));
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  void dispose() {
    nameC.dispose();
    emailC.dispose();
    phoneC.dispose();
    passC.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: sfBg,
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 460),
              child: Container(
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(28),
                  boxShadow: [
                    BoxShadow(
                      // ignore: deprecated_member_use
                      color: Colors.black.withOpacity(0.06),
                      blurRadius: 24,
                      offset: const Offset(0, 10),
                    ),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Center(
                      child: Image.asset(
                        'assets/logo.png',
                        height: 108,
                        fit: BoxFit.contain,
                      ),
                    ),
                    const SizedBox(height: 18),

                    const Center(
                      child: Text(
                        "Create your account",
                        style: TextStyle(
                          fontSize: 26,
                          fontWeight: FontWeight.w900,
                          color: sfText,
                        ),
                      ),
                    ),

                    const SizedBox(height: 8),

                    const Center(
                      child: Text(
                        "Sign up to save your setup and get personalized recommendations.",
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          fontSize: 13.5,
                          height: 1.5,
                          color: sfMuted,
                        ),
                      ),
                    ),

                    const SizedBox(height: 28),

                    _field(
                      controller: nameC,
                      hint: "Full Name",
                      prefix: const Icon(Icons.person_outline),
                    ),

                    const SizedBox(height: 16),

                    _field(
                      controller: emailC,
                      hint: "Email Address",
                      keyboardType: TextInputType.emailAddress,
                      prefix: const Icon(Icons.email_outlined),
                    ),

                    const SizedBox(height: 16),

                    _field(
                      controller: phoneC,
                      hint: "Phone Number",
                      keyboardType: TextInputType.phone,
                      prefix: const Icon(Icons.phone_outlined),
                    ),

                    const SizedBox(height: 16),

                    _field(
                      controller: passC,
                      hint: "Password",
                      obscureText: obscurePass,
                      prefix: const Icon(Icons.lock_outline),
                      suffix: IconButton(
                        onPressed: () {
                          setState(() => obscurePass = !obscurePass);
                        },
                        icon: Icon(
                          obscurePass ? Icons.visibility_off : Icons.visibility,
                        ),
                      ),
                    ),

                    const SizedBox(height: 24),

                    SizedBox(
                      width: double.infinity,
                      height: 54,
                      child: ElevatedButton(
                        onPressed: loading ? null : _signup,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: sfBlue,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        child: loading
                            ? const CircularProgressIndicator(
                                color: Colors.white,
                              )
                            : const Text(
                                "Create Account",
                                style: TextStyle(fontWeight: FontWeight.w800),
                              ),
                      ),
                    ),

                    const SizedBox(height: 16),

                    SizedBox(
                      width: double.infinity,
                      height: 52,
                      child: OutlinedButton(
                        onPressed: () {
                          Navigator.pushReplacementNamed(context, '/login');
                        },
                        child: const Text("Already have an account? Sign In"),
                      ),
                    ),

                    const SizedBox(height: 12),

                    Center(
                      child: TextButton(
                        onPressed: () {
                          Navigator.pushReplacementNamed(context, '/welcome');
                        },
                        child: const Text("Back to Welcome"),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _field({
    required TextEditingController controller,
    required String hint,
    TextInputType? keyboardType,
    bool obscureText = false,
    Widget? prefix,
    Widget? suffix,
  }) {
    return SizedBox(
      height: 56,
      child: TextField(
        controller: controller,
        keyboardType: keyboardType,
        obscureText: obscureText,
        decoration: InputDecoration(
          hintText: hint,
          filled: true,
          fillColor: const Color(0xFFF8FAFF),
          prefixIcon: prefix,
          suffixIcon: suffix,
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(16)),
        ),
      ),
    );
  }
}
