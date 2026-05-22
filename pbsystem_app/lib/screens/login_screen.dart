import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';

import '../services/api_service.dart';
import '../theme/app_colors.dart';
import '../widgets/web_gradient_button.dart';
import 'dashboard_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, this.role = 'user'});
  final String role;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final emailCtl = TextEditingController();
  final pwCtl    = TextEditingController();
  bool loading = false;
  String errorMessage = '';

  Future<void> _handleLogin() async {
    setState(() { loading = true; errorMessage = ''; });
    final api = ApiService();
    final data = await api.login(emailCtl.text.trim(), pwCtl.text.trim(), widget.role);
    if (!mounted) return;
    setState(() => loading = false);
    if (data['success'] == 1) {
      final user = data['user'];
      Navigator.pushReplacement(context, MaterialPageRoute(
        builder: (_) => DashboardScreen(
          userId: user['id'], name: user['name'], email: user['email'], role: widget.role,
        ),
      ));
    } else {
      setState(() => errorMessage = data['message'] ?? 'Login failed');
    }
  }

  @override
  Widget build(BuildContext context) {
    final isAdmin = widget.role == 'admin';
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
                const SizedBox(height: 24),
                Row(
                  mainAxisSize: MainAxisSize.min,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Image.asset('assets/images/uitm.png',
                        height: 56,
                        errorBuilder: (_, __, ___) => const SizedBox.shrink()),
                    Container(
                      width: 1.5, height: 44,
                      color: Colors.white.withValues(alpha: 0.22),
                      margin: const EdgeInsets.symmetric(horizontal: 14),
                    ),
                    Container(
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                      child: Image.asset('assets/images/kik2.png',
                          height: 48,
                          errorBuilder: (_, __, ___) => const SizedBox.shrink()),
                    ),
                  ],
                ),
                const SizedBox(height: 18),
                RichText(
                  textAlign: TextAlign.center,
                  text: TextSpan(
                    style: GoogleFonts.manrope(
                      fontSize: 22,
                      fontWeight: FontWeight.w800,
                      color: Colors.white,
                      letterSpacing: 0.4,
                    ),
                    children: const [
                      TextSpan(text: 'NEO '),
                      TextSpan(text: 'V-TRACK', style: TextStyle(color: AppColors.brandYellow)),
                    ],
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'UiTM SEGAMAT',
                  style: GoogleFonts.spaceGrotesk(
                    fontSize: 11,
                    fontWeight: FontWeight.w600,
                    color: Colors.white.withValues(alpha: 0.65),
                    letterSpacing: 3.6,
                  ),
                ),
                const SizedBox(height: 28),

                Container(
                  constraints: const BoxConstraints(maxWidth: 420),
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.22),
                        blurRadius: 32, offset: const Offset(0, 14),
                      ),
                    ],
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Text(
                        'PAS KENDERAAN',
                        style: GoogleFonts.spaceGrotesk(
                          fontSize: 11, fontWeight: FontWeight.w700,
                          letterSpacing: 1.8, color: AppColors.brandPurple,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Row(
                        children: [
                          FaIcon(isAdmin ? FontAwesomeIcons.userShield : FontAwesomeIcons.user,
                              color: AppColors.brandPurple, size: 20),
                          const SizedBox(width: 10),
                          Text(isAdmin ? 'Sign in as admin' : 'Sign in',
                              style: GoogleFonts.manrope(
                                fontSize: 22, fontWeight: FontWeight.w800,
                                color: AppColors.dark, letterSpacing: -0.3,
                              )),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Text(
                        isAdmin ? 'Administrator account' : 'Continue to your dashboard',
                        style: GoogleFonts.spaceGrotesk(
                          color: AppColors.mutedText, fontSize: 13,
                        ),
                      ),
                      const SizedBox(height: 22),

                      TextField(
                        controller: emailCtl,
                        keyboardType: TextInputType.emailAddress,
                        decoration: const InputDecoration(
                          labelText: 'Email',
                          prefixIcon: Padding(
                            padding: EdgeInsets.symmetric(horizontal: 14),
                            child: FaIcon(FontAwesomeIcons.envelope, size: 16, color: AppColors.primary),
                          ),
                          prefixIconConstraints: BoxConstraints(minWidth: 44),
                        ),
                      ),
                      const SizedBox(height: 12),
                      TextField(
                        controller: pwCtl,
                        obscureText: true,
                        decoration: const InputDecoration(
                          labelText: 'Password',
                          prefixIcon: Padding(
                            padding: EdgeInsets.symmetric(horizontal: 14),
                            child: FaIcon(FontAwesomeIcons.lock, size: 16, color: AppColors.primary),
                          ),
                          prefixIconConstraints: BoxConstraints(minWidth: 44),
                        ),
                      ),

                      if (errorMessage.isNotEmpty) ...[
                        const SizedBox(height: 14),
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: AppColors.danger.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(color: AppColors.danger.withValues(alpha: 0.4)),
                          ),
                          child: Row(children: [
                            const FaIcon(FontAwesomeIcons.circleExclamation, size: 14, color: AppColors.danger),
                            const SizedBox(width: 8),
                            Expanded(child: Text(errorMessage, style: const TextStyle(color: AppColors.danger, fontSize: 12))),
                          ]),
                        ),
                      ],

                      const SizedBox(height: 22),
                      WebGradientButton(
                        label: 'LOGIN',
                        icon: FontAwesomeIcons.rightToBracket,
                        loading: loading,
                        onPressed: loading ? null : _handleLogin,
                      ),
                      const SizedBox(height: 14),
                      Center(child: Wrap(
                        spacing: 4, alignment: WrapAlignment.center,
                        children: [
                          TextButton(
                            onPressed: () => Navigator.pushNamed(context, '/forgot'),
                            child: const Text('Forgot password?'),
                          ),
                          if (!isAdmin)
                            TextButton(
                              onPressed: () => Navigator.pushNamed(context, '/register'),
                              child: const Text('Create account'),
                            ),
                        ],
                      )),
                    ],
                  ),
                ),
                const SizedBox(height: 24),
                Text('© 2026 NEO V-TRACK · UiTM Cawangan Johor',
                    style: TextStyle(color: Colors.white.withValues(alpha: 0.7), fontSize: 11)),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
