import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import '../services/api_service.dart';
import '../theme/app_colors.dart';
import '../widgets/web_app_bar.dart';

/// Single screen for managing either 'user' or 'admin' accounts.
/// Pass [target] = 'user' or 'admin'.
class AccountManagementScreen extends StatefulWidget {
  final String target;
  const AccountManagementScreen({super.key, required this.target})
      : assert(target == 'user' || target == 'admin');

  @override
  State<AccountManagementScreen> createState() =>
      _AccountManagementScreenState();
}

class _AccountManagementScreenState extends State<AccountManagementScreen> {
  final ApiService _api = ApiService();
  final TextEditingController _search = TextEditingController();
  late Future<List<Map<String, dynamic>>> _future;

  String get _title => widget.target == 'admin' ? 'Admins' : 'Users';
  String get _listAction =>
      widget.target == 'admin' ? 'list_admins' : 'list_users';
  String get _addAction =>
      widget.target == 'admin' ? 'add_admin' : 'add_user';
  String get _updAction =>
      widget.target == 'admin' ? 'update_admin' : 'update_user';
  String get _delAction =>
      widget.target == 'admin' ? 'delete_admin' : 'delete_user';
  String get _payloadKey => widget.target == 'admin' ? 'admins' : 'users';

  @override
  void initState() {
    super.initState();
    _future = _load();
    _search.addListener(() => setState(() {}));
  }

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  Future<List<Map<String, dynamic>>> _load() async {
    final res =
        await _api.get('admin_management_api.php?action=$_listAction');
    if (res.statusCode != 200) {
      throw Exception('HTTP ${res.statusCode}: ${res.body}');
    }
    final body = jsonDecode(res.body);
    if (body['success'] != true) {
      throw Exception(body['message'] ?? 'Failed');
    }
    return (body[_payloadKey] as List).cast<Map<String, dynamic>>();
  }

  Future<void> _refresh() async {
    setState(() => _future = _load());
    await _future;
  }

  Future<void> _post(String action, Map<String, String> body) async {
    body['action'] = action;
    final res = await _api.post(
      'admin_management_api.php',
      {'Content-Type': 'application/x-www-form-urlencoded'},
      body,
    );
    final data = res.body.isEmpty ? null : jsonDecode(res.body);
    if (res.statusCode >= 400 || (data is Map && data['success'] != true)) {
      throw Exception((data is Map ? data['message'] : null) ??
          'HTTP ${res.statusCode}');
    }
  }

  Future<Map<String, String>?> _showForm({
    Map<String, dynamic>? existing,
  }) async {
    final emailCtl = TextEditingController(text: existing?['email'] ?? '');
    final nameCtl  = TextEditingController(text: existing?['name']  ?? '');
    final pwCtl    = TextEditingController();
    final formKey  = GlobalKey<FormState>();
    final isEdit   = existing != null;

    final result = await showDialog<Map<String, String>>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isEdit
            ? 'Edit ${widget.target}'
            : 'Add ${widget.target}'),
        content: Form(
          key: formKey,
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextFormField(
                  controller: emailCtl,
                  enabled: !isEdit,
                  decoration: const InputDecoration(labelText: 'Email'),
                  keyboardType: TextInputType.emailAddress,
                  validator: (v) => (v == null || v.isEmpty)
                      ? 'Required'
                      : (!v.contains('@') ? 'Invalid email' : null),
                ),
                TextFormField(
                  controller: nameCtl,
                  decoration: const InputDecoration(labelText: 'Name'),
                  validator: (v) =>
                      (v == null || v.trim().isEmpty) ? 'Required' : null,
                ),
                TextFormField(
                  controller: pwCtl,
                  decoration: InputDecoration(
                    labelText: isEdit ? 'New password (blank = keep)' : 'Password',
                  ),
                  obscureText: true,
                  validator: (v) {
                    if (!isEdit && (v == null || v.length < 6)) {
                      return 'Min 6 characters';
                    }
                    if (isEdit && v != null && v.isNotEmpty && v.length < 6) {
                      return 'Min 6 characters';
                    }
                    return null;
                  },
                ),
              ],
            ),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () {
              if (formKey.currentState!.validate()) {
                Navigator.pop(ctx, {
                  'email': emailCtl.text.trim(),
                  'name':  nameCtl.text.trim(),
                  'password': pwCtl.text,
                });
              }
            },
            child: Text(isEdit ? 'Save' : 'Add'),
          ),
        ],
      ),
    );
    return result;
  }

  Future<void> _onAdd() async {
    final v = await _showForm();
    if (v == null) return;
    try {
      await _post(_addAction, v);
      _refresh();
      _snack('Added.');
    } catch (e) {
      _snack('Error: $e', isError: true);
    }
  }

  Future<void> _onEdit(Map<String, dynamic> row) async {
    final v = await _showForm(existing: row);
    if (v == null) return;
    try {
      final body = <String, String>{
        'id':   row['userid'].toString(),
        'name': v['name']!,
      };
      if ((v['password'] ?? '').isNotEmpty) body['password'] = v['password']!;
      await _post(_updAction, body);
      _refresh();
      _snack('Updated.');
    } catch (e) {
      _snack('Error: $e', isError: true);
    }
  }

  Future<void> _onDelete(Map<String, dynamic> row) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete?'),
        content: Text('Delete ${widget.target} "${row['name']}" (${row['email']})? This cannot be undone.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Delete'),
          ),
        ],
      ),
    );
    if (ok != true) return;
    try {
      await _post(_delAction, {'id': row['userid'].toString()});
      _refresh();
      _snack('Deleted.');
    } catch (e) {
      _snack('Error: $e', isError: true);
    }
  }

  void _snack(String msg, {bool isError = false}) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(msg),
      backgroundColor: isError ? Colors.red : null,
    ));
  }

  @override
  Widget build(BuildContext context) {
    final q = _search.text.trim().toLowerCase();
    return Scaffold(
      backgroundColor: AppColors.lightBg,
      appBar: WebAppBar(title: 'Manage $_title', subtitle: 'Account management'),
      floatingActionButton: FloatingActionButton(
        onPressed: _onAdd,
        backgroundColor: AppColors.primary,
        child: const FaIcon(FontAwesomeIcons.plus, color: Colors.white, size: 16),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(12),
            child: TextField(
              controller: _search,
              decoration: InputDecoration(
                hintText: 'Search by name or email…',
                prefixIcon: const Icon(Icons.search),
                border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(8)),
                isDense: true,
              ),
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _refresh,
              child: FutureBuilder<List<Map<String, dynamic>>>(
                future: _future,
                builder: (ctx, snap) {
                  if (snap.connectionState != ConnectionState.done) {
                    return const Center(child: CircularProgressIndicator());
                  }
                  if (snap.hasError) {
                    return ListView(children: [
                      Padding(
                        padding: const EdgeInsets.all(24),
                        child: Text('${snap.error}',
                            style: const TextStyle(color: Colors.red)),
                      ),
                    ]);
                  }
                  final all = snap.data ?? [];
                  final list = q.isEmpty
                      ? all
                      : all.where((r) {
                          final name  = (r['name']  ?? '').toString().toLowerCase();
                          final email = (r['email'] ?? '').toString().toLowerCase();
                          return name.contains(q) || email.contains(q);
                        }).toList();
                  if (list.isEmpty) {
                    return ListView(children: const [
                      Padding(
                        padding: EdgeInsets.all(40),
                        child: Center(child: Text('No accounts found.')),
                      ),
                    ]);
                  }
                  return ListView.separated(
                    itemCount: list.length,
                    separatorBuilder: (_, __) => const Divider(height: 1),
                    itemBuilder: (ctx, i) {
                      final r = list[i];
                      return ListTile(
                        leading: CircleAvatar(
                          backgroundColor: AppColors.primary.withValues(alpha: 0.1),
                          child: Text(
                            (r['name'] ?? '?').toString().isNotEmpty
                                ? r['name'].toString()[0].toUpperCase()
                                : '?',
                            style: const TextStyle(
                                color: AppColors.primary,
                                fontWeight: FontWeight.bold),
                          ),
                        ),
                        title: Text(r['name'] ?? ''),
                        subtitle: Text(r['email'] ?? ''),
                        trailing: PopupMenuButton<String>(
                          onSelected: (v) {
                            if (v == 'edit') _onEdit(r);
                            if (v == 'delete') _onDelete(r);
                          },
                          itemBuilder: (_) => const [
                            PopupMenuItem(value: 'edit', child: Text('Edit')),
                            PopupMenuItem(value: 'delete', child: Text('Delete')),
                          ],
                        ),
                        onTap: () => _onEdit(r),
                      );
                    },
                  );
                },
              ),
            ),
          ),
        ],
      ),
    );
  }
}
