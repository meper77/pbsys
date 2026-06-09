import 'package:flutter/material.dart';
import '../config.dart';
import '../theme.dart';
import '../services/api.dart';
import '../services/session.dart';
import 'home_screen.dart';

/// Passwordless sign-in: UiTM email -> one-time code. No role picker (the server
/// decides admin vs user from the allowlist).
class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});
  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _email = TextEditingController();
  final _code = TextEditingController();
  final _emailKey = GlobalKey<FormState>();
  bool _busy = false;
  bool _codeSent = false;
  String? _error;
  String? _info;

  @override
  void dispose() {
    _email.dispose();
    _code.dispose();
    super.dispose();
  }

  bool _validUitm(String e) {
    final r = RegExp(r'@(student\.)?uitm\.edu\.my$', caseSensitive: false);
    return e.contains('@') && r.hasMatch(e.trim());
  }

  Future<void> _sendCode() async {
    if (!(_emailKey.currentState?.validate() ?? false)) return;
    setState(() { _busy = true; _error = null; _info = null; });
    try {
      await Api.requestOtp(_email.text.trim());
      setState(() { _codeSent = true; _info = 'We emailed a 6-digit code to ${_email.text.trim()}.'; });
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _verify() async {
    if (_code.text.trim().length < 4) { setState(() => _error = 'Enter the 6-digit code.'); return; }
    setState(() { _busy = true; _error = null; });
    try {
      final user = await Api.verifyOtp(_email.text.trim(), _code.text.trim());
      await Session.save(user);
      if (!mounted) return;
      Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => HomeScreen(user: user)));
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const SizedBox(height: 24),
              Container(
                width: 72,
                height: 72,
                alignment: Alignment.center,
                decoration: BoxDecoration(color: NV.navy, borderRadius: BorderRadius.circular(18)),
                child: const Icon(Icons.directions_car_filled, color: NV.yellow, size: 38),
              ),
              const SizedBox(height: 18),
              const Text('NEO V-TRACK',
                  style: TextStyle(fontSize: 26, fontWeight: FontWeight.w800, color: NV.ink)),
              Text(Config.tagline, style: const TextStyle(color: NV.muted, fontSize: 13)),
              const SizedBox(height: 28),

              if (!_codeSent) ...[
                Form(
                  key: _emailKey,
                  child: TextFormField(
                    controller: _email,
                    keyboardType: TextInputType.emailAddress,
                    decoration: const InputDecoration(
                      labelText: 'UiTM email',
                      hintText: 'name@uitm.edu.my',
                      prefixIcon: Icon(Icons.mail_outline),
                    ),
                    validator: (v) => (v == null || !_validUitm(v))
                        ? 'Use your UiTM email (@uitm.edu.my)'
                        : null,
                  ),
                ),
              ] else ...[
                TextFormField(
                  controller: _email,
                  enabled: false,
                  decoration: const InputDecoration(labelText: 'UiTM email', prefixIcon: Icon(Icons.mail_outline)),
                ),
                const SizedBox(height: 14),
                TextFormField(
                  controller: _code,
                  keyboardType: TextInputType.number,
                  maxLength: 6,
                  textAlign: TextAlign.center,
                  style: const TextStyle(fontSize: 22, letterSpacing: 8),
                  decoration: const InputDecoration(labelText: 'One-time code', counterText: ''),
                ),
              ],

              if (_info != null) ...[
                const SizedBox(height: 12),
                _banner(_info!, NV.navy, Icons.info_outline),
              ],
              if (_error != null) ...[
                const SizedBox(height: 12),
                _banner(_error!, NV.danger, Icons.error_outline),
              ],

              const SizedBox(height: 20),
              FilledButton(
                onPressed: _busy ? null : (_codeSent ? _verify : _sendCode),
                child: _busy
                    ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                    : Text(_codeSent ? 'Verify & sign in' : 'Email me a code'),
              ),
              if (_codeSent) ...[
                const SizedBox(height: 6),
                TextButton(
                  onPressed: _busy ? null : _sendCode,
                  child: const Text('Resend code'),
                ),
                TextButton(
                  onPressed: _busy ? null : () => setState(() { _codeSent = false; _code.clear(); _error = null; _info = null; }),
                  child: const Text('Use a different email'),
                ),
              ],
              const SizedBox(height: 8),
              Text('Connecting to ${Config.apiBaseUrl}',
                  textAlign: TextAlign.center, style: const TextStyle(color: NV.muted, fontSize: 11)),
            ],
          ),
        ),
      ),
    );
  }

  Widget _banner(String text, Color color, IconData icon) => Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(color: color.withValues(alpha: 0.08), borderRadius: BorderRadius.circular(10)),
        child: Row(children: [
          Icon(icon, color: color, size: 18),
          const SizedBox(width: 8),
          Expanded(child: Text(text, style: TextStyle(color: color, fontSize: 13))),
        ]),
      );
}
