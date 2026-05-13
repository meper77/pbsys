import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../services/api_service.dart';

class AdminPanelScreen extends StatelessWidget {
  const AdminPanelScreen({super.key});

  Future<void> _openWebAdmin() async {
    final url = Uri.parse('${ApiService.baseUrl}/admin.php');
    if (await canLaunchUrl(url)) {
      await launchUrl(url);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Admin Panel')),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Administrator Tools',
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 12),
            const Text('This panel mirrors a subset of admin features from the web. Use web admin for full access.'),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: _openWebAdmin,
              child: const Text('Open Web Admin Page'),
            ),
          ],
        ),
      ),
    );
  }
}
