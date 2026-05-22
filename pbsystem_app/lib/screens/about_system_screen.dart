import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';

import '../theme/app_colors.dart';
import '../widgets/web_app_bar.dart';
import '../widgets/web_gradient_button.dart';
import '../widgets/web_section_title.dart';

class AboutSystemScreen extends StatelessWidget {
  const AboutSystemScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.lightBg,
      appBar: const WebAppBar(title: 'About The System', subtitle: 'NEO V-TRACK'),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            Row(
              mainAxisSize: MainAxisSize.min,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Image.asset('assets/images/uitm.png', height: 56,
                    errorBuilder: (_, _, _) => const SizedBox.shrink()),
                Container(
                  width: 1.5, height: 44,
                  color: AppColors.brandPurple.withValues(alpha: 0.18),
                  margin: const EdgeInsets.symmetric(horizontal: 16),
                ),
                Image.asset('assets/images/kik2.png', height: 56,
                    errorBuilder: (_, _, _) => const SizedBox.shrink()),
              ],
            ),
            const SizedBox(height: 20),
            RichText(
              text: const TextSpan(
                style: TextStyle(
                  fontSize: 24, fontWeight: FontWeight.w800,
                  color: AppColors.dark, letterSpacing: 0.4,
                ),
                children: [
                  TextSpan(text: 'NEO '),
                  TextSpan(text: 'V-TRACK',
                      style: TextStyle(color: AppColors.brandPurple)),
                ],
              ),
            ),
            const SizedBox(height: 4),
            const Text('UiTM SEGAMAT · Sistem Pengurusan Kenderaan',
                style: TextStyle(color: AppColors.mutedText, fontSize: 12, letterSpacing: 1.4),
                textAlign: TextAlign.center),
            const SizedBox(height: 24),

            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                border: const Border(top: BorderSide(color: AppColors.uitmRed, width: 3)),
                boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 10, offset: const Offset(0, 4))],
              ),
              child: const Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  WebSectionTitle(title: 'About', icon: FontAwesomeIcons.circleInfo),
                  Text(
                    'NEO V-TRACK is a vehicle management and monitoring system developed to overcome the problem of vehicles parked at inappropriate places.\n\n'
                    'The system functions as a centralized platform for recording and managing vehicle details, driver information, and user status classifications in a structured and controlled manner.\n\n'
                    'With NEO V-TRACK, users can track vehicles, manage access, and maintain order efficiently.',
                    style: TextStyle(fontSize: 14, height: 1.7, color: AppColors.bodyText),
                    textAlign: TextAlign.justify,
                  ),
                ],
              ),
            ),

            const SizedBox(height: 20),
            WebGradientButton(
              label: 'BACK TO DASHBOARD',
              icon: FontAwesomeIcons.arrowLeft,
              onPressed: () => Navigator.pop(context),
            ),
            const SizedBox(height: 18),
            const Text('© 2026 NEO V-TRACK · UiTM Cawangan Johor',
                style: TextStyle(color: AppColors.mutedText, fontSize: 11)),
          ],
        ),
      ),
    );
  }
}
