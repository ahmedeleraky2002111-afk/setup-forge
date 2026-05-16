import 'package:flutter/material.dart';

class SfSearchBar extends StatelessWidget {
  final String hint;
  final VoidCallback? onTap;

  const SfSearchBar({
    super.key,
    this.hint = "Search products, services...",
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: const Color(0x22000000)),
        ),
        child: Row(
          children: [
            const Icon(Icons.search),
            const SizedBox(width: 10),
            Expanded(
              child: Text(hint, style: TextStyle(color: Colors.grey.shade700)),
            ),
            const Icon(Icons.mic_none),
          ],
        ),
      ),
    );
  }
}
