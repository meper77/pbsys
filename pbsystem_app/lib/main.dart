import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_inappwebview/flutter_inappwebview.dart';
import 'package:geolocator/geolocator.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:google_fonts/google_fonts.dart' hide Config;
import 'config.dart';

/// NEO V-TRACK native shell.
///
/// The whole web application (auth, dashboards, admin, vehicle CRUD, search,
/// reports, import …) is hosted inside a native WebView so the app mirrors the
/// entire site, with native plumbing for geolocation, camera/file uploads and
/// file downloads that the web pages rely on.
const Color _navy = Color(0xFF2E1465);
const Color _yellow = Color(0xFFFFC400);

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const NeoVTrackApp());
}

class NeoVTrackApp extends StatelessWidget {
  const NeoVTrackApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: Config.appName,
      debugShowCheckedModeBanner: false,
      theme: ThemeData(useMaterial3: true, colorScheme: ColorScheme.fromSeed(seedColor: _navy)),
      home: const WebShell(),
    );
  }
}

class WebShell extends StatefulWidget {
  const WebShell({super.key});
  @override
  State<WebShell> createState() => _WebShellState();
}

class _WebShellState extends State<WebShell> {
  InAppWebViewController? _controller;
  late final PullToRefreshController _pull;
  double _progress = 0;
  bool _firstLoadDone = false;

  @override
  void initState() {
    super.initState();
    _pull = PullToRefreshController(
      settings: PullToRefreshSettings(color: _navy),
      onRefresh: () async {
        await _controller?.reload();
      },
    );
    _requestLocation();
    // Reveal the web content within a few seconds even if onLoadStop is delayed
    // (e.g. the login page's looping background video keeps the frame "loading").
    Timer(const Duration(seconds: 6), () {
      if (mounted && !_firstLoadDone) setState(() => _firstLoadDone = true);
    });
  }

  Future<void> _requestLocation() async {
    try {
      if (await Geolocator.checkPermission() == LocationPermission.denied) {
        await Geolocator.requestPermission();
      }
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) async {
        if (didPop) return;
        final c = _controller;
        if (c != null && await c.canGoBack()) {
          await c.goBack();
        } else {
          await SystemNavigator.pop();
        }
      },
      child: Scaffold(
        backgroundColor: _navy,
        body: Stack(
          children: [
            SafeArea(
              child: Column(
                children: [
                  if (_firstLoadDone && _progress < 1.0)
                    LinearProgressIndicator(
                      value: _progress == 0 ? null : _progress,
                      minHeight: 2.5,
                      backgroundColor: const Color(0xFFEDE9F5),
                      color: _navy,
                    ),
                  Expanded(
                    child: InAppWebView(
                      initialUrlRequest: URLRequest(url: WebUri(Config.apiBaseUrl)),
                      initialSettings: InAppWebViewSettings(
                        javaScriptEnabled: true,
                        domStorageEnabled: true,
                        useOnDownloadStart: true,
                        geolocationEnabled: true,
                        mediaPlaybackRequiresUserGesture: false,
                        allowsInlineMediaPlayback: true,
                        useHybridComposition: true,
                        supportZoom: false,
                        mixedContentMode: MixedContentMode.MIXED_CONTENT_ALWAYS_ALLOW,
                        // Present as Chrome (not a "; wv" WebView) so Google Identity
                        // Services renders the Sign-In button and allows the flow.
                        userAgent:
                            "Mozilla/5.0 (Linux; Android 14; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Mobile Safari/537.36 NEOVTRACKAPP/1.0",
                      ),
                      pullToRefreshController: _pull,
                      onWebViewCreated: (c) => _controller = c,
                      onConsoleMessage: (c, m) => debugPrint('[wv] ${m.message}'),
                      onProgressChanged: (c, p) => setState(() {
                        _progress = p / 100.0;
                        if (p >= 100) _firstLoadDone = true;
                      }),
                      onLoadStop: (c, url) async {
                        _pull.endRefreshing();
                        if (!_firstLoadDone) setState(() => _firstLoadDone = true);
                      },
                      onReceivedError: (c, req, err) async {
                        _pull.endRefreshing();
                        if (!_firstLoadDone) setState(() => _firstLoadDone = true);
                      },
                      // TESTING: the server reuses an expired TLS cert to enable HTTPS
                      // (Google Sign-In needs a secure origin). Proceed past the cert
                      // warning so the site loads. Remove once a valid cert is in place.
                      onReceivedServerTrustAuthRequest: (c, challenge) async =>
                          ServerTrustAuthResponse(action: ServerTrustAuthResponseAction.PROCEED),
                      onPermissionRequest: (c, req) async => PermissionResponse(
                        resources: req.resources,
                        action: PermissionResponseAction.GRANT,
                      ),
                      onGeolocationPermissionsShowPrompt: (c, origin) async =>
                          GeolocationPermissionShowPromptResponse(origin: origin, allow: true, retain: true),
                      onDownloadStartRequest: (c, req) async {
                        if (await canLaunchUrl(req.url)) {
                          await launchUrl(req.url, mode: LaunchMode.externalApplication);
                        }
                      },
                    ),
                  ),
                ],
              ),
            ),
            if (!_firstLoadDone) _splash(),
          ],
        ),
      ),
    );
  }

  Widget _splash() => Container(
        color: _navy,
        child: Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              // Manrope = the web's --font-display (the "NEO V-TRACK" branding font).
              Text('NEO V-TRACK',
                  style: GoogleFonts.manrope(
                      color: Colors.white, fontSize: 28, fontWeight: FontWeight.w800, letterSpacing: 0.3)),
              const SizedBox(height: 8),
              // Space Grotesk = the web's --font-sans (body text).
              Text(Config.tagline,
                  style: GoogleFonts.spaceGrotesk(
                      color: Colors.white.withValues(alpha: 0.7), fontSize: 12.5, letterSpacing: 0.2)),
              const SizedBox(height: 30),
              const SizedBox(
                  width: 26, height: 26, child: CircularProgressIndicator(color: _yellow, strokeWidth: 2.5)),
            ],
          ),
        ),
      );
}
