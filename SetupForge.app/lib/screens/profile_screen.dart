import 'package:flutter/material.dart';
import '../services/api_service.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  static const Color _blue = Color(0xFF004CAC);
  static const Color _teal = Color(0xFF009994);
  static const Color _bg = Color(0xFFF5F7FB);
  static const Color _textDark = Color(0xFF121212);
  static const Color _textMuted = Color(0xFF6C757D);
  static const Color _border = Color(0x1A000000);

  void _toast(BuildContext context, String msg) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(msg), duration: const Duration(seconds: 1)),
    );
  }

  Future<void> _logout(BuildContext context) async {
    try {
      final api = ApiService();
      await api.clearToken();

      if (!context.mounted) return;

      Navigator.pushNamedAndRemoveUntil(context, '/welcome', (route) => false);
    } catch (e) {
      if (!context.mounted) return;
      _toast(context, "Logout error: $e");
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _bg,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        foregroundColor: _textDark,
        title: const Text(
          "Profile",
          style: TextStyle(fontWeight: FontWeight.w900),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _buildProfileHeader(),
          const SizedBox(height: 16),
          _buildJourneyCard(),
          const SizedBox(height: 16),
          _buildSectionTitle("Account"),
          const SizedBox(height: 10),
          _actionTile(
            icon: Icons.login_rounded,
            title: "Login",
            subtitle: "Access your account and continue your setup",
            onTap: () => Navigator.pushNamed(context, '/login'),
          ),
          const SizedBox(height: 10),
          _actionTile(
            icon: Icons.person_add_alt_1_rounded,
            title: "Create Account",
            subtitle: "Save your setup progress and recommendations",
            onTap: () => Navigator.pushNamed(context, '/signup'),
          ),
          const SizedBox(height: 10),
          _actionTile(
            icon: Icons.settings_outlined,
            title: "Settings",
            subtitle: "Manage app preferences",
            onTap: () => _toast(context, "Settings screen not added yet"),
          ),
          const SizedBox(height: 10),
          _actionTile(
            icon: Icons.logout_rounded,
            title: "Logout",
            subtitle: "Sign out from the current session",
            danger: true,
            onTap: () => _logout(context),
          ),
          const SizedBox(height: 18),
          _buildSectionTitle("Quick Access"),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: _miniCard(
                  icon: Icons.home_rounded,
                  title: "Home",
                  onTap: () => Navigator.pushNamed(context, '/app-shell'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _miniCard(
                  icon: Icons.build_rounded,
                  title: "Setup",
                  onTap: () => Navigator.pushNamed(context, '/setup'),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _miniCard(
                  icon: Icons.inventory_2_rounded,
                  title: "Packages",
                  onTap: () => Navigator.pushNamed(context, '/packages'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _miniCard(
                  icon: Icons.shopping_bag_outlined,
                  title: "Orders",
                  onTap: () => _toast(context, "Orders screen not added yet"),
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  Widget _buildProfileHeader() {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [_blue, _teal],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(22),
        boxShadow: [
          BoxShadow(
            color: _blue.withOpacity(0.12),
            blurRadius: 18,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: const Row(
        children: [
          CircleAvatar(
            radius: 30,
            backgroundColor: Colors.white24,
            child: Icon(Icons.person_rounded, color: Colors.white, size: 30),
          ),
          SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  "Your SetupForge Profile",
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w900,
                    color: Colors.white,
                  ),
                ),
                SizedBox(height: 4),
                Text(
                  "Manage your setup journey, account access, and quick actions.",
                  style: TextStyle(
                    fontSize: 12.5,
                    height: 1.4,
                    color: Colors.white,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildJourneyCard() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: _border),
      ),
      child: const Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            "Your Journey",
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w900,
              color: _textDark,
            ),
          ),
          SizedBox(height: 8),
          Text(
            "Use your account to keep your business setup progress, review generated packages, and continue placing orders anytime.",
            style: TextStyle(
              fontSize: 13,
              height: 1.5,
              color: _textMuted,
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Text(
      title,
      style: const TextStyle(
        fontSize: 16,
        fontWeight: FontWeight.w900,
        color: _textDark,
      ),
    );
  }

  Widget _actionTile({
    required IconData icon,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
    bool danger = false,
  }) {
    final Color iconColor = danger ? Colors.red : _blue;
    final Color titleColor = danger ? Colors.red : _textDark;

    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: _border),
          ),
          child: Row(
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: danger
                      ? Colors.red.withOpacity(0.10)
                      : _blue.withOpacity(0.10),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(icon, color: iconColor),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w800,
                        color: titleColor,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      subtitle,
                      style: const TextStyle(
                        fontSize: 12,
                        height: 1.4,
                        color: _textMuted,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ),
              ),
              const Icon(Icons.chevron_right_rounded, color: _textMuted),
            ],
          ),
        ),
      ),
    );
  }

  Widget _miniCard({
    required IconData icon,
    required String title,
    required VoidCallback onTap,
  }) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 18, horizontal: 14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: _border),
          ),
          child: Column(
            children: [
              Icon(icon, color: _blue),
              const SizedBox(height: 10),
              Text(
                title,
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w800,
                  color: _textDark,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
