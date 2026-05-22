import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';

import '../services/api_service.dart';
import '../theme/app_colors.dart';
import '../widgets/web_gradient_button.dart';

class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({super.key});
  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  final emailCtl   = TextEditingController();
  final pwCtl      = TextEditingController();
  final confirmCtl = TextEditingController();
  bool loading = false;
  String message = '';
  bool isError = false;

  Future<void> _handleReset() async {
    setState(() { loading = true; message = ''; });
    final api = ApiService();
    final data = await api.resetPassword(
      emailCtl.text.trim(), pwCtl.text.trim(), confirmCtl.text.trim(),
    );
    if (!mounted) return;
    setState(() {
      loading = false;
      message = data['message'] ?? 'Reset failed';
      isError = data['success'] != 1;
    });
    if (data['success'] == 1) {
      Navigator.pushReplacementNamed(context, '/login_user');
    }
  }

  Widget _field({
    required TextEditingController controller,
    required String label,
    required IconData icon,
    bool obscure = false,
    TextInputType? keyboard,
  }) =>
      Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: TextField(
          controller: controller,
          obscureText: obscure,
          keyboardType: keyboard,
          decoration: InputDecoration(
            labelText: label,
            prefixIcon: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 14),
              child: FaIcon(icon, size: 16, color: AppColors.primary),
            ),
            prefixIconConstraints: const BoxConstraints(minWidth: 44),
          ),
        ),
      );

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft, end: Alignment.bottomRight,
            colors: AppColors.heroGradient,
          ),
        ),
        child: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: Column(
              children: [
                Container(height: 3, color: AppColors.uitmRed),
                const SizedBox(height: 28),
                const Text('Reset Your Password',
                    style: TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.w800)),
                const SizedBox(height: 6),
                Text('Enter your email and new password below',
                    style: TextStyle(color: Colors.white.withValues(alpha: 0.8), fontSize: 13),
                    textAlign: TextAlign.center),
                const SizedBox(height: 30),
                Container(
                  constraints: const BoxConstraints(maxWidth: 420),
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(14),
                    border: const Border(top: BorderSide(color: AppColors.uitmRed, width: 3)),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.2),
                        blurRadius: 20, offset: const Offset(0, 10),
                      ),
                    ],
                  ),
                  child: Column(
                    children: [
                      _field(controller: emailCtl, label: 'Email', icon: FontAwesomeIcons.envelope, keyboard: TextInputType.emailAddress),
                      _field(controller: pwCtl, label: 'New Password', icon: FontAwesomeIcons.lock, obscure: true),
                      _field(controller: confirmCtl, label: 'Confirm Password', icon: FontAwesomeIcons.lock, obscure: true),
                      if (message.isNotEmpty) ...[
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: (isError ? AppColors.danger : AppColors.success).withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(color: (isError ? AppColors.danger : AppColors.success).withValues(alpha: 0.4)),
                          ),
                          child: Row(children: [
                            FaIcon(isError ? FontAwesomeIcons.circleExclamation : FontAwesomeIcons.circleCheck,
                                size: 14, color: isError ? AppColors.danger : AppColors.success),
                            const SizedBox(width: 8),
                            Expanded(child: Text(message, style: TextStyle(
                              color: isError ? AppColors.danger : AppColors.success, fontSize: 12,
                            ))),
                          ]),
                        ),
                        const SizedBox(height: 10),
                      ],
                      const SizedBox(height: 4),
                      WebGradientButton(
                        label: 'RESET PASSWORD',
                        icon: FontAwesomeIcons.keyboard,
                        loading: loading,
                        onPressed: loading ? null : _handleReset,
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                TextButton(
                  onPressed: () => Navigator.pushReplacementNamed(context, '/login_user'),
                  child: Text('Back to Login',
                      style: TextStyle(color: Colors.white.withValues(alpha: 0.9), fontWeight: FontWeight.w600)),
                ),
                const SizedBox(height: 12),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
