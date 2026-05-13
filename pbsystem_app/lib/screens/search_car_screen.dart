import 'package:flutter/material.dart';
import 'package:shimmer/shimmer.dart';
import '../services/api_service.dart';
import 'package:url_launcher/url_launcher.dart';
import '../screens/vehicle_detail_screen.dart';

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
  String activeStatus = '';
  bool showAll = false;

  // ==== UiTM Colors ====
  static const primaryColor = Color(0xFF4B2E83); // Ungu
  static const secondaryColor = Color(0xFFF3C143); // Kuning Emas
  static const neutralWhite = Color(0xFFFFFFFF);
  static const textPrimary = Color(0xFF000000);
  static const cardBackground = Color(0xFFF6F2FC);

  Future<void> _performSearch({
    String search = '',
    String status = '',
    bool showAllRecords = false,
    bool requireQuery = false,
  }) async {
    final query = search.trim();
    if (requireQuery && query.isEmpty) {
      setState(() {
        message = 'PLEASE ENTER PLATE NUMBER, OWNER NAME, OR ID';
        results = [];
        activeStatus = '';
        showAll = false;
      });
      return;
    }

    setState(() {
      loading = true;
      message = '';
      results = [];
      activeStatus = status;
      showAll = showAllRecords;
    });

    final data = await api.searchCarUser(
      search: query,
      status: status,
      showAll: showAllRecords,
    );

    setState(() {
      loading = false;
      if (data['success'] == 1) {
        results = data['data'];
        if (results.isEmpty) {
          message = 'NO VEHICLES FOUND';
        }
      } else {
        message = (data['message'] ?? 'SEARCH FAILED').toUpperCase();
      }
    });
  }

  Future<void> handleSearch() async {
    await _performSearch(
      search: searchController.text,
      requireQuery: true,
    );
  }

  Color _statusColor(String status) {
    final normalized = status.toLowerCase();
    if (normalized.contains('staf') || normalized.contains('staff')) {
      return const Color(0xFF1976D2);
    }
    if (normalized.contains('pelajar') || normalized.contains('student')) {
      return const Color(0xFF7B1FA2);
    }
    if (normalized.contains('pelawat') || normalized.contains('visitor')) {
      return const Color(0xFF388E3C);
    }
    if (normalized.contains('kontraktor') || normalized.contains('contractor')) {
      return const Color(0xFFF57C00);
    }
    return primaryColor;
  }

  String _searchSummary() {
    if (showAll) {
      return 'ALL VEHICLES (${results.length})';
    }
    if (activeStatus.isNotEmpty) {
      return '${activeStatus.toUpperCase()} VEHICLES (${results.length})';
    }
    if (searchController.text.trim().isNotEmpty) {
      return 'SEARCH RESULTS (${results.length})';
    }
    return 'SEARCH RESULTS';
  }

  Widget _filterButton({
    required String label,
    required bool selected,
    required VoidCallback onTap,
  }) {
    return Padding(
      padding: const EdgeInsets.only(right: 8, bottom: 8),
      child: OutlinedButton(
        onPressed: loading ? null : onTap,
        style: OutlinedButton.styleFrom(
          backgroundColor: selected ? secondaryColor : Colors.white,
          foregroundColor: selected ? primaryColor : primaryColor,
          side: BorderSide(
            color: selected ? secondaryColor : primaryColor.withOpacity(0.4),
          ),
        ),
        child: Text(label),
      ),
    );
  }

  Widget _statusBadge(String status) {
    final color = _statusColor(status);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withOpacity(0.12),
        borderRadius: BorderRadius.circular(6),
        border: Border.all(color: color.withOpacity(0.5)),
      ),
      child: Text(
        status.toUpperCase(),
        style: TextStyle(
          color: color,
          fontWeight: FontWeight.w700,
          fontSize: 12,
        ),
      ),
    );
  }

  Widget _rowLabel(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(top: 4),
      child: RichText(
        text: TextSpan(
          style: const TextStyle(color: textPrimary, fontSize: 14),
          children: [
            TextSpan(
              text: '$label: ',
              style: const TextStyle(fontWeight: FontWeight.w700),
            ),
            TextSpan(text: value.isEmpty ? '-' : value),
          ],
        ),
      ),
    );
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

                const SizedBox(height: 14),

                Wrap(
                  children: [
                    _filterButton(
                      label: 'STAFF',
                      selected: activeStatus == 'Staf' && !showAll,
                      onTap: () => _performSearch(status: 'Staf'),
                    ),
                    _filterButton(
                      label: 'STUDENT',
                      selected: activeStatus == 'Pelajar' && !showAll,
                      onTap: () => _performSearch(status: 'Pelajar'),
                    ),
                    _filterButton(
                      label: 'VISITOR',
                      selected: activeStatus == 'Pelawat' && !showAll,
                      onTap: () => _performSearch(status: 'Pelawat'),
                    ),
                    _filterButton(
                      label: 'CONTRACTOR',
                      selected: activeStatus == 'Kontraktor' && !showAll,
                      onTap: () => _performSearch(status: 'Kontraktor'),
                    ),
                    _filterButton(
                      label: 'ALL',
                      selected: showAll,
                      onTap: () => _performSearch(showAllRecords: true),
                    ),
                  ],
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

                if (results.isNotEmpty)
                  Align(
                    alignment: Alignment.centerLeft,
                    child: Text(
                      _searchSummary(),
                      style: const TextStyle(
                        color: neutralWhite,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),

                if (results.isNotEmpty) const SizedBox(height: 12),

                // ==== RESULTS ====
                ListView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: results.length,
                  itemBuilder: (context, index) {
                    final item = results[index];
                    final phone = (item['phone'] ?? '').toString().trim();
                    final sticker = (item['sticker'] ?? '').toString().trim();

                    return Card(
                      margin: const EdgeInsets.only(bottom: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      color: cardBackground,
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  item['platenum']?.toString().toUpperCase() ?? '-',
                                  style: const TextStyle(
                                    fontWeight: FontWeight.bold,
                                    color: primaryColor,
                                    fontSize: 18,
                                  ),
                                ),
                                _statusBadge((item['status'] ?? '-').toString()),
                              ],
                            ),
                            const SizedBox(height: 6),
                            _rowLabel(
                              'OWNER',
                              (item['name'] ?? '').toString().toUpperCase(),
                            ),
                            _rowLabel(
                              'ID NUMBER',
                              (item['idnumber'] ?? '').toString().toUpperCase(),
                            ),
                            Row(
                              children: [
                                const Text('PHONE: '),
                                GestureDetector(
                                  onTap: () async {
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
                            _rowLabel(
                              'TYPE',
                              (item['type'] ?? '').toString().toUpperCase(),
                            ),
                            _rowLabel(
                              'STICKER',
                              sticker.isEmpty ? '-' : sticker.toUpperCase(),
                            ),
                            const SizedBox(height: 8),
                            SizedBox(
                              width: double.infinity,
                              child: OutlinedButton(
                                onPressed: () {
                                  Navigator.of(context).push(
                                    MaterialPageRoute(builder: (_) => VehicleDetailScreen(vehicle: item)),
                                  );
                                },
                                style: OutlinedButton.styleFrom(
                                  foregroundColor: primaryColor,
                                ),
                                child: const Text('VIEW DETAILS'),
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),

                const SizedBox(height: 8),
                if (results.isNotEmpty || message.isNotEmpty)
                  SizedBox(
                    width: double.infinity,
                    child: OutlinedButton(
                      onPressed: () {
                        setState(() {
                          searchController.clear();
                          activeStatus = '';
                          showAll = false;
                          results = [];
                          message = '';
                        });
                      },
                      style: OutlinedButton.styleFrom(
                        foregroundColor: neutralWhite,
                        side: const BorderSide(color: neutralWhite),
                      ),
                      child: const Text('RESET SEARCH'),
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
