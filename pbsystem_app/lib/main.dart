import 'package:flutter/material.dart';
import 'config.dart';
import 'theme.dart';
import 'services/session.dart';
import 'screens/login_screen.dart';
import 'screens/home_screen.dart';

void main() => runApp(const NeoVTrackApp());

class NeoVTrackApp extends StatelessWidget {
  const NeoVTrackApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: Config.appName,
      debugShowCheckedModeBanner: false,
      theme: NV.theme(),
      home: const _Boot(),
    );
  }
}

/// Splash that restores the session then routes to Home or Login.
class _Boot extends StatefulWidget {
  const _Boot();
  @override
  State<_Boot> createState() => _BootState();
}

class _BootState extends State<_Boot> {
  @override
  void initState() {
    super.initState();
    _go();
  }

  Future<void> _go() async {
    final user = await Session.load();
    await Future.delayed(const Duration(milliseconds: 600));
    if (!mounted) return;
    Navigator.of(context).pushReplacement(MaterialPageRoute(
      builder: (_) => user == null ? const LoginScreen() : HomeScreen(user: user),
    ));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: NV.navy,
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(20)),
              child: const Icon(Icons.directions_car_filled, color: NV.yellow, size: 56),
            ),
            const SizedBox(height: 20),
            const Text('NEO V-TRACK',
                style: TextStyle(
                    color: Colors.white, fontSize: 24, fontWeight: FontWeight.w800, letterSpacing: 0.5)),
            const SizedBox(height: 6),
            Text(Config.tagline, style: TextStyle(color: Colors.white.withValues(alpha: 0.7), fontSize: 12)),
            const SizedBox(height: 28),
            const SizedBox(
                width: 26, height: 26, child: CircularProgressIndicator(color: NV.yellow, strokeWidth: 2.5)),
          ],
        ),
      ),
    );
  }
}
