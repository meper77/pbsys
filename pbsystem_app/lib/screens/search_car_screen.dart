import 'package:flutter/material.dart';
import 'package:shimmer/shimmer.dart';
import '../services/api_service.dart';
import 'package:url_launcher/url_launcher.dart';

class SearchCarScreen extends StatefulWidget {
  const SearchCarScreen({super.key});

  @override
  State<SearchCarScreen> createState() => _SearchCarScreenState();
}

class _SearchCarScreenState extends State<SearchCarScreen> {
  final TextEditingController searchController = TextEditingController();
  final ApiService api = ApiService();

  bool loading = false;
  String message = '';
  List<dynamic> results = [];

  // ==== UiTM Colors ====
  static const primaryColor = Color(0xFF4B2E83); // Ungu
  static const secondaryColor = Color(0xFFF3C143); // Kuning Emas
  static const neutralWhite = Color(0xFFFFFFFF);
  static const textPrimary = Color(0xFF000000);

  Future<void> handleSearch() async {
    final query = searchController.text.trim();

    if (query.isEmpty) {
      setState(() {
        message = 'PLEASE ENTER PLATE NUMBER, OWNER NAME, OR ID';
        results = [];
      });
      return;
    }

    setState(() {
      loading = true;
      message = '';
      results = [];
    });

    final data = await api.searchCarUser(search: query);

    setState(() {
      loading = false;
      if (data['success'] == 1) {
        results = data['data'];
        if (results.isEmpty) message = 'NO VEHICLES FOUND';
      } else {
        message = (data['message'] ?? 'SEARCH FAILED').toUpperCase();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        width: double.infinity,
        height: double.infinity,
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [primaryColor, Color(0xFF6A1B9A)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
        child: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: Column(
              children: [
                const SizedBox(height: 20),

                // ==== TITLE ====
                const Text(
                  'SEARCH VEHICLES',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: neutralWhite,
                  ),
                ),

                const SizedBox(height: 20),

                // ==== SEARCH FIELD ====
                TextField(
                  controller: searchController,
                  textCapitalization: TextCapitalization.characters,
                  decoration: InputDecoration(
                    labelText: 'PLATE NUMBER / OWNER / ID',
                    prefixIcon: const Icon(Icons.search, color: primaryColor),
                    filled: true,
                    fillColor: neutralWhite,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide.none,
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // ==== SEARCH BUTTON WITH SHIMMER ====
                SizedBox(
                  width: double.infinity,
                  height: 52,
                  child: ElevatedButton(
                    onPressed: loading ? null : handleSearch,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: secondaryColor,
                      foregroundColor: primaryColor,
                    ),
                    child: loading
                        ? Shimmer.fromColors(
                            baseColor: primaryColor,
                            highlightColor: neutralWhite,
                            period: const Duration(seconds: 2),
                            child: const Text(
                              'SEARCHING...',
                              style: TextStyle(
                                  fontWeight: FontWeight.bold, fontSize: 16),
                            ),
                          )
                        : const Text(
                            'SEARCH',
                            style: TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 16,
                                color: primaryColor),
                          ),
                  ),
                ),

                const SizedBox(height: 20),

                // ==== MESSAGE ====
                if (message.isNotEmpty)
                  Text(
                    message,
                    style: const TextStyle(color: Colors.red, fontWeight: FontWeight.bold),
                    textAlign: TextAlign.center,
                  ),

                const SizedBox(height: 16),

                // ==== RESULTS ====
                ListView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: results.length,
                  itemBuilder: (context, index) {
                    final item = results[index];
                    return Card(
                      margin: const EdgeInsets.only(bottom: 12),
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12)),
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              item['platenum']?.toString().toUpperCase() ?? '-',
                              style: const TextStyle(
                                  fontWeight: FontWeight.bold,
                                  color: primaryColor),
                            ),
                            const SizedBox(height: 6),
                            Text('OWNER: ${item['name']?.toUpperCase() ?? '-'}'),
                            Text('ID: ${item['idnumber']?.toUpperCase() ?? '-'}'),
                            Row(
                              children: [
                                const Text('PHONE: '),
                                GestureDetector(
                                  onTap: () async {
                                    final phone = item['phone'] ?? '';
                                    if (phone.isNotEmpty) {
                                      final uri = Uri.parse('tel:$phone');
                                      if (await canLaunchUrl(uri)) {
                                        await launchUrl(uri);
                                      }
                                    }
                                  },
                                  child: Text(
                                    item['phone'] ?? '-',
                                    style: const TextStyle(
                                      color: Colors.blue,
                                      decoration: TextDecoration.underline,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            Text('STATUS: ${item['status']?.toUpperCase() ?? '-'}'),
                            Text('TYPE: ${item['type']?.toUpperCase() ?? '-'}'),
                          ],
                        ),
                      ),
                    );
                  },
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}