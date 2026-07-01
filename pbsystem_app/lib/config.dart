/// App-wide configuration.
///
/// Base URL points at the Hestia production host by default and is overridable
/// at build time:  flutter build apk --dart-define=API_BASE_URL=http://10.0.26.208
class Config {
  static const String appName = 'NEO V-TRACK';
  static const String tagline = 'UiTM Cawangan Johor · Polis Bantuan';

  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://neovtrack.uitm.edu.my',
  );

  /// Build a full URL for an `/api/*` endpoint.
  static Uri api(String path, [Map<String, String>? query]) {
    final clean = path.startsWith('/') ? path.substring(1) : path;
    return Uri.parse('$apiBaseUrl/api/$clean').replace(queryParameters: query);
  }
}
