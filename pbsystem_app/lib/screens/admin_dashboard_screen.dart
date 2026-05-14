import 'dart:convert';
import 'package:flutter/material.dart';
import '../services/api_service.dart';

class AdminDashboardScreen extends StatefulWidget {
  const AdminDashboardScreen({super.key});

  @override
  State<AdminDashboardScreen> createState() => _AdminDashboardScreenState();
}

class _AdminDashboardScreenState extends State<AdminDashboardScreen> {
  final ApiService _apiService = ApiService();
  late Future<Map<String, dynamic>> _statsData;
  bool _isAdmin = false;

  @override
  void initState() {
    super.initState();
    _checkAdminRole();
    _statsData = _fetchStatistics();
  }

  void _checkAdminRole() {
    // Check if user is admin (userid <= 10 or has admin role)
    setState(() {
      _isAdmin = ApiService.currentUserRole.toLowerCase() == 'admin' ||
          ApiService.currentUserId <= 10;
    });
  }

  Future<Map<String, dynamic>> _fetchStatistics() async {
    try {
      final response = await _apiService.get('vehicle_stats_api.php');
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          return data['data'] ?? {};
        }
      }
      return {};
    } catch (e) {
      print('Error fetching statistics: $e');
      return {};
    }
  }

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: _isAdmin ? 4 : 1,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Admin Dashboard'),
          bottom: _isAdmin
              ? TabBar(
                  tabs: const [
                    Tab(text: 'Statistics'),
                    Tab(text: 'Users'),
                    Tab(text: 'Stickers'),
                    Tab(text: 'Vehicles'),
                  ],
                )
              : null,
        ),
        body: _isAdmin ? _buildAdminView() : _buildNonAdminView(),
      ),
    );
  }

  Widget _buildAdminView() {
    return TabBarView(
      children: [
        _buildStatisticsTab(),
        _buildUsersTab(),
        _buildStickersTab(),
        _buildVehiclesTab(),
      ],
    );
  }

  Widget _buildNonAdminView() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.lock, size: 64, color: Colors.grey[400]),
          const SizedBox(height: 16),
          Text(
            'Admin Access Required',
            style: Theme.of(context).textTheme.titleLarge,
          ),
          const SizedBox(height: 8),
          Text(
            'You do not have permission to access the admin dashboard.',
            style: Theme.of(context).textTheme.bodyMedium,
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildStatisticsTab() {
    return FutureBuilder<Map<String, dynamic>>(
      future: _statsData,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Center(child: CircularProgressIndicator());
        }

        if (snapshot.hasError) {
          return Center(child: Text('Error: ${snapshot.error}'));
        }

        final stats = snapshot.data ?? {};
        return SingleChildScrollView(
          child: Padding(
            padding: const EdgeInsets.all(16.0),
            child: Column(
              children: [
                _buildStatCard(
                  'Staff Vehicles',
                  stats['staff_count']?.toString() ?? '0',
                  Icons.directions_car,
                  Colors.blue,
                  () => _navigateToVehicleList(context, 'staffcar'),
                ),
                const SizedBox(height: 12),
                _buildStatCard(
                  'Student Vehicles',
                  stats['student_count']?.toString() ?? '0',
                  Icons.school,
                  Colors.green,
                  () => _navigateToVehicleList(context, 'studentcar'),
                ),
                const SizedBox(height: 12),
                _buildStatCard(
                  'Visitor Vehicles',
                  stats['visitor_count']?.toString() ?? '0',
                  Icons.person,
                  Colors.orange,
                  () => _navigateToVehicleList(context, 'visitorcar'),
                ),
                const SizedBox(height: 12),
                _buildStatCard(
                  'Contractor Vehicles',
                  stats['contractor_count']?.toString() ?? '0',
                  Icons.business,
                  Colors.purple,
                  () => _navigateToVehicleList(context, 'contractorcar'),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildStatCard(String title, String count, IconData icon, Color color,
      VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: color.withOpacity(0.2),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(icon, color: color, size: 32),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title, style: const TextStyle(fontSize: 14)),
                    Text(count,
                        style: const TextStyle(
                            fontSize: 24, fontWeight: FontWeight.bold)),
                  ],
                ),
              ),
              Icon(Icons.arrow_forward, color: Colors.grey[400]),
            ],
          ),
        ),
      ),
    );
  }

  void _navigateToVehicleList(BuildContext context, String type) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) =>
            AdminVehicleListScreen(vehicleType: type),
      ),
    );
  }

  Widget _buildUsersTab() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.people, size: 64, color: Colors.grey[400]),
            const SizedBox(height: 16),
            const Text('User Management'),
            const SizedBox(height: 8),
            const Text('Add and manage admin users from the web panel'),
            const SizedBox(height: 24),
            ElevatedButton.icon(
              onPressed: () {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                      content: Text('Use web panel for user management')),
                );
              },
              icon: const Icon(Icons.open_in_browser),
              label: const Text('Open Web Panel'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStickersTab() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.label, size: 64, color: Colors.grey[400]),
            const SizedBox(height: 16),
            const Text('Sticker Management'),
            const SizedBox(height: 8),
            const Text('Remove or manage vehicle stickers'),
            const SizedBox(height: 24),
            ElevatedButton.icon(
              onPressed: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => const AdminStickerScreen(),
                  ),
                );
              },
              icon: const Icon(Icons.edit),
              label: const Text('Manage Stickers'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildVehiclesTab() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.directions_car, size: 64, color: Colors.grey[400]),
            const SizedBox(height: 16),
            const Text('Vehicle Management'),
            const SizedBox(height: 8),
            const Text('View and manage all vehicles'),
            const SizedBox(height: 24),
            ElevatedButton.icon(
              onPressed: () {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                      content: Text('Use web panel for vehicle management')),
                );
              },
              icon: const Icon(Icons.open_in_browser),
              label: const Text('Open Web Panel'),
            ),
          ],
        ),
      ),
    );
  }
}

class AdminVehicleListScreen extends StatefulWidget {
  final String vehicleType;

  const AdminVehicleListScreen({
    super.key,
    required this.vehicleType,
  });

  @override
  State<AdminVehicleListScreen> createState() => _AdminVehicleListScreenState();
}

class _AdminVehicleListScreenState extends State<AdminVehicleListScreen> {
  final ApiService _apiService = ApiService();
  late Future<List<dynamic>> _vehicleList;

  @override
  void initState() {
    super.initState();
    _vehicleList = _fetchVehicles();
  }

  Future<List<dynamic>> _fetchVehicles() async {
    try {
      final response = await _apiService
          .get('vehicle_list_drill_down.php?type=${widget.vehicleType}');
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          return data['data'] ?? [];
        }
      }
      return [];
    } catch (e) {
      print('Error fetching vehicles: $e');
      return [];
    }
  }

  @override
  Widget build(BuildContext context) {
    final typeDisplayName = {
      'staffcar': 'Staff Vehicles',
      'studentcar': 'Student Vehicles',
      'visitorcar': 'Visitor Vehicles',
      'contractorcar': 'Contractor Vehicles',
    }[widget.vehicleType] ??
        'Vehicles';

    return Scaffold(
      appBar: AppBar(
        title: Text(typeDisplayName),
      ),
      body: FutureBuilder<List<dynamic>>(
        future: _vehicleList,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }

          if (snapshot.hasError) {
            return Center(child: Text('Error: ${snapshot.error}'));
          }

          final vehicles = snapshot.data ?? [];
          if (vehicles.isEmpty) {
            return const Center(child: Text('No vehicles found'));
          }

          return ListView.builder(
            itemCount: vehicles.length,
            itemBuilder: (context, index) {
              final vehicle = vehicles[index];
              return Card(
                margin: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                child: ListTile(
                  leading: Icon(
                    _getVehicleIcon(widget.vehicleType),
                    color: _getVehicleColor(widget.vehicleType),
                  ),
                  title: Text(vehicle['platenum'] ?? '-'),
                  subtitle: Text(
                    '${vehicle['name'] ?? '-'} (${vehicle['brand'] ?? '-'})',
                  ),
                  trailing: const Icon(Icons.arrow_forward),
                  onTap: () {
                    _showVehicleDetails(context, vehicle);
                  },
                ),
              );
            },
          );
        },
      ),
    );
  }

  IconData _getVehicleIcon(String type) {
    switch (type) {
      case 'staffcar':
        return Icons.directions_car;
      case 'studentcar':
        return Icons.school;
      case 'visitorcar':
        return Icons.person;
      case 'contractorcar':
        return Icons.business;
      default:
        return Icons.directions_car;
    }
  }

  Color _getVehicleColor(String type) {
    switch (type) {
      case 'staffcar':
        return Colors.blue;
      case 'studentcar':
        return Colors.green;
      case 'visitorcar':
        return Colors.orange;
      case 'contractorcar':
        return Colors.purple;
      default:
        return Colors.grey;
    }
  }

  void _showVehicleDetails(BuildContext context, Map<String, dynamic> vehicle) {
    showModalBottomSheet(
      context: context,
      builder: (context) => Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Vehicle Details',
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(height: 16),
            _buildDetailRow('Plate Number', vehicle['platenum'] ?? '-'),
            _buildDetailRow('Owner Name', vehicle['name'] ?? '-'),
            _buildDetailRow('ID/Passport', vehicle['idnumber'] ?? '-'),
            _buildDetailRow('Phone', vehicle['phone'] ?? '-'),
            _buildDetailRow('Brand', vehicle['brand'] ?? '-'),
            _buildDetailRow('Status', vehicle['status'] ?? '-'),
            _buildDetailRow('Created', vehicle['created_at'] ?? '-'),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Close'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDetailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12.0),
      child: Row(
        children: [
          SizedBox(
            width: 100,
            child: Text(
              label,
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
          ),
          Expanded(
            child: Text(value),
          ),
        ],
      ),
    );
  }
}

class AdminStickerScreen extends StatefulWidget {
  const AdminStickerScreen({super.key});

  @override
  State<AdminStickerScreen> createState() => _AdminStickerScreenState();
}

class _AdminStickerScreenState extends State<AdminStickerScreen> {
  final ApiService _apiService = ApiService();
  final TextEditingController _searchController = TextEditingController();
  List<dynamic> _vehicles = [];
  List<dynamic> _filteredVehicles = [];
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _searchController.addListener(_filterVehicles);
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _searchVehicles() async {
    if (_searchController.text.isEmpty) {
      setState(() => _filteredVehicles = []);
      return;
    }

    setState(() => _isLoading = true);
    try {
      final response = await _apiService
          .get('search_api.php?plate=${_searchController.text}');
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          setState(() {
            _vehicles = data['data'] ?? [];
            _filterVehicles();
          });
        }
      }
    } catch (e) {
      print('Error searching vehicles: $e');
    }
    setState(() => _isLoading = false);
  }

  void _filterVehicles() {
    setState(() {
      _filteredVehicles = _vehicles;
    });
  }

  Future<void> _removeStickerForVehicle(
      String plateNum, Map<String, dynamic> vehicle) async {
    try {
      final response = await _apiService.post(
        'sticker_management_api.php',
        {'Content-Type': 'application/json'},
        jsonEncode({
          'action': 'remove',
          'platenum': plateNum,
          'type': vehicle['type'] ?? 'unknown',
        }),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Sticker removed successfully')),
          );
          _searchVehicles();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Error: ${data['message']}')),
          );
        }
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Manage Stickers')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16.0),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _searchController,
                    decoration: InputDecoration(
                      hintText: 'Search by plate number',
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(8),
                      ),
                      contentPadding: const EdgeInsets.symmetric(
                        horizontal: 12,
                        vertical: 8,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                ElevatedButton(
                  onPressed: _searchVehicles,
                  child: const Text('Search'),
                ),
              ],
            ),
          ),
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _filteredVehicles.isEmpty
                    ? const Center(
                        child: Text('Search for a vehicle to remove sticker'),
                      )
                    : ListView.builder(
                        itemCount: _filteredVehicles.length,
                        itemBuilder: (context, index) {
                          final vehicle = _filteredVehicles[index];
                          return Card(
                            margin: const EdgeInsets.symmetric(
                                horizontal: 8, vertical: 4),
                            child: ListTile(
                              title: Text(vehicle['platenum'] ?? '-'),
                              subtitle: Text(
                                '${vehicle['name'] ?? '-'} (${vehicle['type'] ?? '-'})',
                              ),
                              trailing: ElevatedButton(
                                onPressed: () => _removeStickerForVehicle(
                                  vehicle['platenum'],
                                  vehicle,
                                ),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: Colors.red,
                                ),
                                child: const Text('Remove'),
                              ),
                            ),
                          );
                        },
                      ),
          ),
        ],
      ),
    );
  }
}
