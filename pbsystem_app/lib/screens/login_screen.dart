import 'package:flutter/material.dart';
import '../services/api_service.dart';
import 'dashboard_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, this.role = 'user'});

  final String role;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final TextEditingController emailController = TextEditingController();
  final TextEditingController passwordController = TextEditingController();

  bool loading = false;
  String errorMessage = '';

  // ==== UiTM Official Colors ====
  static const primaryColor = Color(0xFF4B2E83); // Ungu UiTM
  static const secondaryColor = Color(0xFFF3C143); // Kuning Emas
  static const neutralWhite = Color(0xFFFFFFFF);
  static const neutralGrey = Color(0xFFF5F5F5);
  static const textPrimary = Color(0xFF000000);
  static const textSecondary = Color(0xFF333333);

  Future<void> handleLogin() async {
    setState(() {
      loading = true;
      errorMessage = '';
    });

    final api = ApiService();
    final data = await api.login(
      emailController.text.trim(),
      passwordController.text.trim(),
      widget.role,
    );

    setState(() => loading = false);

    if (data['success'] == 1) {
      final user = data['user'];
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(
          builder: (_) => DashboardScreen(
            userId: user['id'],
            name: user['name'],
            email: user['email'],
            role: widget.role,
          ),
        ),
      );
    } else {
      setState(() {
        errorMessage = data['message'] ?? 'Login failed';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final isAdmin = widget.role == 'admin';

    return Scaffold(
      body: Container(
        width: double.infinity,
        height: double.infinity,
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [
              primaryColor,
              Color(0xFF6A1B9A),
              Color(0xFF7B1FA2),
            ],
          ),
        ),
        child: Stack(
          children: [
            // ==== Glow frames ====
            _glowFrame(top: -120, left: -80, rotation: -0.5, color: secondaryColor.withOpacity(0.3)),
            _glowFrame(bottom: -140, right: -100, rotation: 0.5, color: secondaryColor.withOpacity(0.3)),

            SafeArea(
              child: Center(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.all(24),
                  child: Column(
                    children: [
                      const SizedBox(height: 40),

                      // ==== Logos ====
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Image.asset('assets/images/uitm.png', height: 70),
                          const SizedBox(width: 20),
                          Image.asset('assets/images/kik2.png', height: 70),
                        ],
                      ),

                      const SizedBox(height: 20),

                      // ==== Branding Title ====
                      const Text(
                        'NEO V-TRACK',
                        style: TextStyle(
                          fontSize: 32,
                          fontWeight: FontWeight.bold,
                          color: neutralWhite,
                          letterSpacing: 1.2,
                          shadows: [
                            Shadow(
                              blurRadius: 6,
                              color: Color(0xFFFCF1C3), // subtle gold shadow
                              offset: Offset(2, 2),
                            ),
                          ],
                        ),
                      ),

                      const SizedBox(height: 8),

                      Text(
                        'Vehicle Management & Monitoring System',
                        style: TextStyle(
                          color: neutralWhite.withOpacity(0.9),
                          fontSize: 14,
                        ),
                        textAlign: TextAlign.center,
                      ),

                      const SizedBox(height: 50),

                      // ==== Login Card ====
                      Container(
                        constraints: const BoxConstraints(maxWidth: 420),
                        padding: const EdgeInsets.all(28),
                        decoration: BoxDecoration(
                          color: neutralWhite,
                          borderRadius: BorderRadius.circular(18),
                          boxShadow: const [
                            BoxShadow(
                              color: Colors.black26,
                              blurRadius: 25,
                              offset: Offset(0, 14),
                            ),
                          ],
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              isAdmin ? 'Admin Login' : 'Login',
                              style: TextStyle(
                                fontSize: 22,
                                fontWeight: FontWeight.bold,
                                color: textPrimary,
                              ),
                            ),
                            const SizedBox(height: 6),
                            Text(
                              isAdmin ? 'Admin account sign in' : 'Please sign in to continue',
                              style: TextStyle(color: textSecondary),
                            ),
                            const SizedBox(height: 26),

                            _inputField(
                              controller: emailController,
                              label: 'Email Address',
                              icon: Icons.email_outlined,
                            ),
                            const SizedBox(height: 18),
                            _inputField(
                              controller: passwordController,
                              label: 'Password',
                              icon: Icons.lock_outline,
                              obscure: true,
                            ),

                            if (errorMessage.isNotEmpty) ...[
                              const SizedBox(height: 16),
                              Text(
                                errorMessage,
                                style: const TextStyle(color: Colors.red, fontSize: 13),
                              ),
                            ],

                            const SizedBox(height: 28),

                            SizedBox(
                              width: double.infinity,
                              height: 52,
                              child: ElevatedButton(
                                onPressed: loading ? null : handleLogin,
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: secondaryColor,
                                  foregroundColor: primaryColor,
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                  elevation: 6,
                                ),
                                child: loading
                                    ? const SizedBox(
                                        width: 22,
                                        height: 22,
                                        child: CircularProgressIndicator(
                                          strokeWidth: 2,
                                          color: Colors.white,
                                        ),
                                      )
                                    : const Text(
                                        'LOGIN',
                                        style: TextStyle(
                                          fontSize: 16,
                                          fontWeight: FontWeight.bold,
                                          color: Colors.white,
                                        ),
                                      ),
                              ),
                            ),

                            const SizedBox(height: 18),
                            Center(
                              child: Column(
                                children: [
                                  TextButton(
                                    onPressed: () => Navigator.pushNamed(context, '/forgot'),
                                    child: const Text('Forgot your password?'),
                                  ),
                            if (!isAdmin)
                              TextButton(
                                onPressed: () => Navigator.pushNamed(context, '/register'),
                                child: const Text('New user? Create an account'),
                              ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 40),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ==== Input Field ====
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
        labelStyle: const TextStyle(color: textSecondary),
        prefixIcon: Icon(icon, color: primaryColor),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
        ),
      ),
    );
  }

  // ==== Glow Frame ====
  Widget _glowFrame({
    double? top,
    double? bottom,
    double? left,
    double? right,
    required double rotation,
    required Color color,
  }) {
    return Positioned(
      top: top,
      bottom: bottom,
      left: left,
      right: right,
      child: Transform.rotate(
        angle: rotation,
        child: Container(
          width: 320,
          height: 320,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(40),
            border: Border.all(
              color: color.withOpacity(0.2),
              width: 2,
            ),
            boxShadow: [
              BoxShadow(
                color: color.withOpacity(0.5),
                blurRadius: 30,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
