import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

class WebAppScreen extends StatefulWidget {
  const WebAppScreen({super.key});

  @override
  State<WebAppScreen> createState() => _WebAppScreenState();
}

class _WebAppScreenState extends State<WebAppScreen> {
  static const String _homeUrl = 'http://neovtrack.uitm.edu.my/roleSelection.php';
  bool _opened = false;
  String _message = 'Opening web version...';

  @override
  void initState() {
    super.initState();
    _openWebVersion();
  }

  Future<void> _openWebVersion() async {
    if (_opened) {
      return;
    }

    final uri = Uri.parse(_homeUrl);
    final launched = await launchUrl(
      uri,
      mode: LaunchMode.externalApplication,
    );

    if (!mounted) {
      return;
    }

    if (launched) {
      setState(() {
        _opened = true;
        _message = 'Web version opened. Return here anytime to reopen.';
      });
    } else {
      setState(() {
        _message = 'Could not open web version. Tap the button below to retry.';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.language, size: 56, color: Color(0xFF4B2E83)),
                const SizedBox(height: 16),
                const Text(
                  'NEO V-TRACK Web Version',
                  style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 12),
                Text(
                  _message,
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _openWebVersion,
                    child: const Text('Open Web Version'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
