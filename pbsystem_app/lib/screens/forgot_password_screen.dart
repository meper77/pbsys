import 'package:flutter/material.dart';
import '../services/api_service.dart';

class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({super.key});

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  final TextEditingController emailController = TextEditingController();
  final TextEditingController passwordController = TextEditingController();
  final TextEditingController confirmController = TextEditingController();

  bool loading = false;
  String message = '';

  // ===== UiTM Colors =====
  static const Color primaryColor = Color(0xFF4B2E83); // Ungu UiTM
  static const Color secondaryColor = Color(0xFFF3C143); // Kuning Emas
  static const Color neutralWhite = Color(0xFFFFFFFF);
  static const Color textPrimary = Color(0xFF000000);
  static const Color textDarkGrey = Color(0xFF333333);

  Future<void> handleReset() async {
    setState(() {
      loading = true;
      message = '';
    });

    final api = ApiService();
    final data = await api.resetPassword(
      emailController.text.trim(),
      passwordController.text.trim(),
      confirmController.text.trim(),
    );

    setState(() {
      loading = false;
      message = data['message'] ?? 'Reset failed';
    });

    if (data['success'] == 1) {
      Navigator.pushReplacementNamed(context, '/login_user');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          // ===== Background gradient =====
          Container(
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [
                  primaryColor,
                  Color(0xFF5C3A99),
                  Color(0xFF6A4BB3),
                ],
              ),
            ),
          ),

          SafeArea(
            child: Center(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(24),
                child: Column(
                  children: [
                    const SizedBox(height: 40),

                    // ===== Header Banner =====
                    Text(
                      'Reset Your Password',
                      style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                            color: neutralWhite,
                            fontWeight: FontWeight.bold,
                          ),
                      textAlign: TextAlign.center,
                    ),

                    const SizedBox(height: 16),

                    Text(
                      'Enter your email and new password below',
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                            color: neutralWhite.withOpacity(0.85),
                          ),
                      textAlign: TextAlign.center,
                    ),

                    const SizedBox(height: 40),

                    // ===== Input Card =====
                    Container(
                      padding: const EdgeInsets.all(24),
                      decoration: BoxDecoration(
                        color: neutralWhite,
                        borderRadius: BorderRadius.circular(18),
                        boxShadow: const [
                          BoxShadow(
                            color: Colors.black26,
                            blurRadius: 20,
                            offset: Offset(0, 10),
                          ),
                        ],
                      ),
                      child: Column(
                        children: [
                          _inputField(
                            controller: emailController,
                            label: 'Email',
                            icon: Icons.email,
                          ),
                          const SizedBox(height: 16),
                          _inputField(
                            controller: passwordController,
                            label: 'New Password',
                            icon: Icons.lock,
                            obscure: true,
                          ),
                          const SizedBox(height: 16),
                          _inputField(
                            controller: confirmController,
                            label: 'Confirm Password',
                            icon: Icons.lock,
                            obscure: true,
                          ),
                          const SizedBox(height: 24),

                          // ===== Message =====
                          if (message.isNotEmpty)
                            Text(
                              message,
                              style: TextStyle(
                                color: secondaryColor,
                                fontWeight: FontWeight.w600,
                              ),
                            ),

                          const SizedBox(height: 20),

                          // ===== Reset Button =====
                          SizedBox(
                            width: double.infinity,
                            height: 52,
                            child: ElevatedButton(
                              onPressed: loading ? null : handleReset,
                              style: ElevatedButton.styleFrom(
                                backgroundColor: primaryColor,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(12),
                                ),
                              ),
                              child: loading
                                  ? const CircularProgressIndicator(
                                      color: neutralWhite,
                                    )
                                  : const Text(
                                      'RESET PASSWORD',
                                      style: TextStyle(
                                        color: neutralWhite,
                                        fontSize: 16,
                                        fontWeight: FontWeight.bold,
                                      ),
                                    ),
                            ),
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 30),

                    // ===== Redirect to Login =====
                    TextButton(
                      onPressed: () =>
                          Navigator.pushReplacementNamed(context, '/login_user'),
                      child: Text(
                        'Back to Login',
                        style: TextStyle(
                          color: neutralWhite,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  // ===== Input Field Widget =====
  Widget _inputField({
    required TextEditingController controller,
    required String label,
    required IconData icon,
    bool obscure = false,
  }) {
    return TextField(
      controller: controller,
      obscureText: obscure,
      style: const TextStyle(color: textPrimary),
      decoration: InputDecoration(
        labelText: label,
        labelStyle: const TextStyle(color: textDarkGrey),
        prefixIcon: Icon(icon, color: primaryColor),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
        ),
      ),
    );
  }
}



