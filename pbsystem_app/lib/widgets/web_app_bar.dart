import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';

import '../theme/app_colors.dart';

/// One link in the WebAppBar horizontal nav row.
class WebNavTab {
  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final bool active;
  const WebNavTab({required this.icon, required this.label, required this.onTap, this.active = false});
}

/// Replacement for [AppBar] that matches the web `.navbar-black`: solid black
/// background, 3 px UiTM-red bottom border, white text, optional system title
/// + subtitle, and an optional horizontal navigation row (`tabs`) mirroring
/// the web's <nav> link list.
class WebAppBar extends StatelessWidget implements PreferredSizeWidget {
  final String title;
  final String? subtitle;
  final List<Widget>? actions;
  final Widget? leading;
  final bool showLogo;
  final List<WebNavTab>? tabs;

  const WebAppBar({
    super.key,
    required this.title,
    this.subtitle,
    this.actions,
    this.leading,
    this.showLogo = true,
    this.tabs,
  });

  @override
  Size get preferredSize {
    final h = 61.0 + (tabs != null && tabs!.isNotEmpty ? 44 : 0);
    return Size.fromHeight(h + 3); // +3 for red border
  }

  @override
  Widget build(BuildContext context) {
    return Material(
      color: AppColors.brandPurpleDeep,
      elevation: 0,
      child: SafeArea(
        bottom: false,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            SizedBox(
              height: 61,
              child: Row(
                children: [
                  if (leading != null) leading!,
                  if (leading == null && Navigator.of(context).canPop())
                    IconButton(
                      icon: const Icon(Icons.arrow_back, color: Colors.white),
                      onPressed: () => Navigator.of(context).maybePop(),
                    ),
                  if (showLogo && leading == null && !Navigator.of(context).canPop())
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 12),
                      child: Image.asset(
                        'assets/images/kik2.png',
                        width: 36, height: 36,
                        errorBuilder: (_, __, ___) => const SizedBox(width: 36),
                      ),
                    ),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(title, overflow: TextOverflow.ellipsis,
                            style: const TextStyle(color: Colors.white, fontSize: 17, fontWeight: FontWeight.w700)),
                        if (subtitle != null)
                          Text(subtitle!, overflow: TextOverflow.ellipsis,
                              style: TextStyle(color: Colors.white.withValues(alpha: 0.85), fontSize: 11)),
                      ],
                    ),
                  ),
                  if (actions != null) ...actions!,
                  const SizedBox(width: 6),
                ],
              ),
            ),
            if (tabs != null && tabs!.isNotEmpty)
              Container(
                height: 44,
                width: double.infinity,
                decoration: BoxDecoration(
                  color: AppColors.brandPurpleDeep,
                  border: Border(top: BorderSide(color: Colors.white.withValues(alpha: 0.1))),
                ),
                child: SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: tabs!.map(_tab).toList(),
                  ),
                ),
              ),
            Container(height: 3, color: AppColors.uitmRed),
          ],
        ),
      ),
    );
  }

  Widget _tab(WebNavTab t) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: t.onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
          decoration: BoxDecoration(
            border: Border(
              bottom: BorderSide(
                color: t.active ? AppColors.uitmRed : Colors.transparent,
                width: 3,
              ),
              right: BorderSide(color: Colors.white.withValues(alpha: 0.08)),
            ),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              FaIcon(t.icon, size: 12, color: t.active ? Colors.white : Colors.white.withValues(alpha: 0.75)),
              const SizedBox(width: 6),
              Text(
                t.label,
                style: TextStyle(
                  color: t.active ? Colors.white : Colors.white.withValues(alpha: 0.85),
                  fontSize: 12,
                  fontWeight: t.active ? FontWeight.w700 : FontWeight.w500,
                  letterSpacing: 0.3,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
