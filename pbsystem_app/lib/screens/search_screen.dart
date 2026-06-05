import 'dart:async';
import 'package:flutter/material.dart';
import '../models.dart';
import '../theme.dart';
import '../services/api.dart';
import '../services/session.dart';
import 'vehicle_detail_screen.dart';

class SearchScreen extends StatefulWidget {
  const SearchScreen({super.key});
  @override
  State<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends State<SearchScreen> {
  final _controller = TextEditingController();
  Timer? _debounce;
  List<Vehicle> _results = [];
  bool _loading = false;
  bool _searched = false;
  String? _error;

  @override
  void dispose() {
    _debounce?.cancel();
    _controller.dispose();
    super.dispose();
  }

  void _onChanged(String q) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 350), () => _run(q));
  }

  Future<void> _run(String q) async {
    if (q.trim().length < 2) {
      setState(() {
        _results = [];
        _searched = false;
      });
      return;
    }
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final r = await Api.search(q);
      if (!mounted) return;
      setState(() {
        _results = r;
        _searched = true;
      });
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final reporter = Session.current ?? AppUser(id: 0, name: '', email: '', role: 'user');
    return Scaffold(
      appBar: AppBar(title: const Text('Search vehicles')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: TextField(
              controller: _controller,
              autofocus: true,
              textCapitalization: TextCapitalization.characters,
              onChanged: _onChanged,
              decoration: InputDecoration(
                hintText: 'Plate, owner name, or ID…',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: _loading
                    ? const Padding(
                        padding: EdgeInsets.all(12),
                        child: SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2)),
                      )
                    : (_controller.text.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () {
                              _controller.clear();
                              _run('');
                            },
                          )
                        : null),
              ),
            ),
          ),
          Expanded(child: _body(reporter)),
        ],
      ),
    );
  }

  Widget _body(AppUser reporter) {
    if (_error != null) {
      return _hint(Icons.error_outline, _error!, NV.danger);
    }
    if (!_searched) {
      return _hint(Icons.search, 'Type at least 2 characters to search.', NV.muted);
    }
    if (_results.isEmpty) {
      return _hint(Icons.inbox_outlined, 'No matching vehicles.', NV.muted);
    }
    return ListView.separated(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
      itemCount: _results.length,
      separatorBuilder: (_, _) => const SizedBox(height: 8),
      itemBuilder: (_, i) => VehicleTile(
        vehicle: _results[i],
        onTap: () => Navigator.push(
          context,
          MaterialPageRoute(builder: (_) => VehicleDetailScreen(vehicle: _results[i], reporter: reporter)),
        ),
      ),
    );
  }

  Widget _hint(IconData icon, String text, Color color) => Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            Icon(icon, size: 44, color: color.withValues(alpha: 0.6)),
            const SizedBox(height: 12),
            Text(text, textAlign: TextAlign.center, style: TextStyle(color: color)),
          ]),
        ),
      );
}
