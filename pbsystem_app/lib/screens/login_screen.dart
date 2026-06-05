import 'package:flutter/material.dart';
import '../config.dart';
import '../theme.dart';
import '../services/api.dart';
import '../services/session.dart';
import 'home_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});
  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _email = TextEditingController();
  final _password = TextEditingController();
  final _formKey = GlobalKey<FormState>();
  String _role = 'admin';
  bool _busy = false;
  bool _obscure = true;
  String? _error;

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _login() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final user = await Api.login(_email.text.trim(), _password.text, _role);
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
              SegmentedButton<String>(
                segments: const [
                  ButtonSegment(value: 'admin', label: Text('Admin'), icon: Icon(Icons.shield_outlined)),
                  ButtonSegment(value: 'user', label: Text('User'), icon: Icon(Icons.person_outline)),
                ],
                selected: {_role},
                onSelectionChanged: (s) => setState(() => _role = s.first),
              ),
              const SizedBox(height: 20),
              Form(
                key: _formKey,
                child: Column(
                  children: [
                    TextFormField(
                      controller: _email,
                      keyboardType: TextInputType.emailAddress,
                      decoration: const InputDecoration(labelText: 'Email', prefixIcon: Icon(Icons.mail_outline)),
                      validator: (v) => (v == null || !v.contains('@')) ? 'Enter a valid email' : null,
                    ),
                    const SizedBox(height: 14),
                    TextFormField(
                      controller: _password,
                      obscureText: _obscure,
                      decoration: InputDecoration(
                        labelText: 'Password',
                        prefixIcon: const Icon(Icons.lock_outline),
                        suffixIcon: IconButton(
                          icon: Icon(_obscure ? Icons.visibility_off : Icons.visibility),
                          onPressed: () => setState(() => _obscure = !_obscure),
                        ),
                      ),
                      validator: (v) => (v == null || v.isEmpty) ? 'Enter your password' : null,
                    ),
                  ],
                ),
              ),
              if (_error != null) ...[
                const SizedBox(height: 14),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                      color: NV.danger.withValues(alpha: 0.08), borderRadius: BorderRadius.circular(10)),
                  child: Row(children: [
                    const Icon(Icons.error_outline, color: NV.danger, size: 18),
                    const SizedBox(width: 8),
                    Expanded(child: Text(_error!, style: const TextStyle(color: NV.danger, fontSize: 13))),
                  ]),
                ),
              ],
              const SizedBox(height: 22),
              FilledButton(
                onPressed: _busy ? null : _login,
                child: _busy
                    ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                    : const Text('Sign in'),
              ),
              const SizedBox(height: 12),
              Text('Connecting to ${Config.apiBaseUrl}',
                  textAlign: TextAlign.center, style: const TextStyle(color: NV.muted, fontSize: 11)),
            ],
          ),
        ),
      ),
    );
  }
}
