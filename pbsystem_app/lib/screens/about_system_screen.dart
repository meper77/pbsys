import 'package:flutter/material.dart';

class AboutSystemScreen extends StatelessWidget {
  const AboutSystemScreen({super.key});

  // ===== UiTM Colors =====
  static const Color primaryColor = Color(0xFF4B2E83); // Ungu UiTM
  static const Color secondaryColor = Color(0xFFF3C143); // Kuning Emas
  static const Color neutralWhite = Color(0xFFFFFFFF);
  static const Color textDarkGrey = Color(0xFF333333);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.deepPurple.shade100, // ✅ Light purple background

      appBar: AppBar(
        title: const Text(
          'About The System',
          style: TextStyle(color: Colors.white), // ✅ White title
        ),
        backgroundColor: primaryColor,
        iconTheme: const IconThemeData(color: Colors.white),
      ),

      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            // ===== Logos =====
            Image.asset(
              'assets/images/uitm.png',
              height: 80,
            ),
            const SizedBox(height: 20),
            Image.asset(
              'assets/images/kik2.png',
              height: 80,
            ),
            const SizedBox(height: 24),

            // ===== App Title =====
            const Text(
              'NEO.V-TRACK',
              style: TextStyle(
                fontSize: 28,
                fontWeight: FontWeight.bold,
                color: primaryColor,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 12),

            // ===== Subtitle =====
            Text(
              'Vehicle Management & Monitoring System',
              style: TextStyle(
                fontSize: 16,
                color: textDarkGrey.withOpacity(0.8),
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 24),

            // ===== Description Card =====
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(24), // ✅ Equal spacing inside box
              decoration: BoxDecoration(
                color: neutralWhite,
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(
                    color: primaryColor.withOpacity(0.2),
                    blurRadius: 20,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              child: const Text(
                "NEO.V-TRACK is a vehicle management and monitoring system developed to overcome the problem of vehicles parked at inappropriate places.\n\n"
                "The system functions as a centralized platform for recording and managing vehicle details, driver information, and user status classifications in a structured and controlled manner.\n\n"
                "With NEO.V-TRACK, users can track vehicles, manage access, and maintain order efficiently.",
                style: TextStyle(
                  fontSize: 15,
                  height: 1.7, // ✅ Consistent line spacing
                ),
                textAlign: TextAlign.justify,
              ),
            ),

            const SizedBox(height: 30),

            // ===== Back to Dashboard Button =====
            SizedBox(
              width: double.infinity,
              height: 50,
              child: ElevatedButton(
                onPressed: () => Navigator.pop(context),
                style: ElevatedButton.styleFrom(
                  backgroundColor: primaryColor,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: const Text(
                  'BACK TO DASHBOARD',
                  style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: neutralWhite),
                ),
              ),
            ),

            const SizedBox(height: 20),

            // ===== Footer =====
            Text(
              '© 2026 NEO.V-TRACK. All rights reserved.',
              style: TextStyle(
                color: textDarkGrey.withOpacity(0.7),
                fontSize: 12,
              ),
            ),
          ],
        ),
      ),
    );
  }
}