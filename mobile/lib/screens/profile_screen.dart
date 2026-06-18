import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';
import '../services/api_service.dart';
import '../theme.dart';
import '../models/user.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final _nameController = TextEditingController();
  final _departmentController = TextEditingController();
  final _levelController = TextEditingController();
  final _emailController = TextEditingController();
  String _enrollmentYear = '';
  String _memberSince = '';
  String _lastLogin = '';
  bool _loading = true;
  bool _saving = false;
  bool _editing = false;

  final _currentPasswordController = TextEditingController();
  final _newPasswordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();
  bool _changingPassword = false;

  @override
  void initState() {
    super.initState();
    _loadProfile();
  }

  @override
  void dispose() {
    _nameController.dispose();
    _departmentController.dispose();
    _levelController.dispose();
    _emailController.dispose();
    _currentPasswordController.dispose();
    _newPasswordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  Future<void> _loadProfile() async {
    try {
      final response = await ApiService.getStudentProfile();
      if (response['success']) {
        final data = response['data'];
        setState(() {
          _nameController.text = data['full_name'] ?? '';
          _departmentController.text = data['department'] ?? '';
          _levelController.text = '${data['level'] ?? 0}';
          _emailController.text = data['email'] ?? '';
          _enrollmentYear = '${data['enrollment_year'] ?? ''}';
          _memberSince = data['created_at'] ?? '';
          _lastLogin = data['last_login'] ?? '';
          _loading = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _saveProfile() async {
    setState(() => _saving = true);
    try {
      final response = await ApiService.updateProfile(
        _nameController.text.trim(),
        _departmentController.text.trim(),
        int.tryParse(_levelController.text.trim()) ?? 0,
      );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(response['success']
                ? 'Profile updated!'
                : (response['message'] ?? 'Failed')),
            backgroundColor:
                response['success'] ? AppTheme.secondary : AppTheme.error,
          ),
        );
        if (response['success']) setState(() => _editing = false);
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
              content: Text('Network error'), backgroundColor: AppTheme.error),
        );
      }
    }
    if (mounted) setState(() => _saving = false);
  }

  Future<void> _changePassword() async {
    if (_newPasswordController.text != _confirmPasswordController.text) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
            content: Text('Passwords do not match'),
            backgroundColor: AppTheme.error),
      );
      return;
    }
    setState(() => _changingPassword = true);
    try {
      final response = await ApiService.changePassword(
        _currentPasswordController.text.trim(),
        _newPasswordController.text.trim(),
      );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(response['success']
                ? 'Password changed!'
                : (response['message'] ?? 'Failed')),
            backgroundColor:
                response['success'] ? AppTheme.secondary : AppTheme.error,
          ),
        );
        if (response['success']) {
          _currentPasswordController.clear();
          _newPasswordController.clear();
          _confirmPasswordController.clear();
        }
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
              content: Text('Network error'), backgroundColor: AppTheme.error),
        );
      }
    }
    if (mounted) setState(() => _changingPassword = false);
  }

  void _logout() async {
    await context.read<AuthProvider>().logout();
    if (mounted) {
      Navigator.of(context).pushNamedAndRemoveUntil('/login', (route) => false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final user = context.watch<AuthProvider>().user;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Profile'),
        actions: [
          IconButton(
            icon: Icon(_editing ? Icons.close_rounded : Icons.edit_rounded),
            onPressed: () => setState(() => _editing = !_editing),
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                _buildHeader(user),
                const SizedBox(height: 24),
                _buildProfileCard(),
                const SizedBox(height: 16),
                _buildPasswordCard(),
                const SizedBox(height: 16),
                _buildLogoutButton(),
                const SizedBox(height: 24),
              ],
            ),
    );
  }

  Widget _buildHeader(User? user) {
    final initial = (user?.name ?? 'S')[0].toUpperCase();

    return Center(
      child: Column(
        children: [
          Container(
            width: 88,
            height: 88,
            decoration: const BoxDecoration(
              gradient: LinearGradient(colors: AppTheme.primaryGradient),
              shape: BoxShape.circle,
              boxShadow: [
                BoxShadow(
                    color: Color(0x4D4F46E5),
                    blurRadius: 12,
                    offset: Offset(0, 4)),
              ],
            ),
            child: Center(
              child: Text(initial,
                  style: const TextStyle(
                      fontSize: 36,
                      fontWeight: FontWeight.bold,
                      color: Colors.white)),
            ),
          ),
          const SizedBox(height: 16),
          Text(user?.name ?? 'Student',
              style: const TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.bold,
                  color: AppTheme.textPrimary)),
          const SizedBox(height: 4),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
            decoration: BoxDecoration(
              color: AppTheme.primary.withOpacity(0.1),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text(user?.role ?? 'student',
                style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                    color: AppTheme.primary)),
          ),
        ],
      ),
    );
  }

  Widget _buildProfileCard() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: AppTheme.surface,
        borderRadius: BorderRadius.circular(AppTheme.radiusXl),
        border: Border.all(color: AppTheme.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            children: [
              Icon(Icons.person_outline, size: 20, color: AppTheme.primary),
              SizedBox(width: 8),
              Text('Personal Information',
                  style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: AppTheme.textPrimary)),
            ],
          ),
          const SizedBox(height: 20),
          if (_editing) ...[
            TextFormField(
              controller: _nameController,
              style: const TextStyle(fontSize: 15),
              decoration: const InputDecoration(
                  labelText: 'Full Name',
                  prefixIcon: Icon(Icons.person_outline, size: 20)),
            ),
            const SizedBox(height: 14),
            TextFormField(
              controller: _departmentController,
              style: const TextStyle(fontSize: 15),
              decoration: const InputDecoration(
                  labelText: 'Department',
                  prefixIcon: Icon(Icons.school_outlined, size: 20)),
            ),
            const SizedBox(height: 14),
            TextFormField(
              controller: _levelController,
              keyboardType: TextInputType.number,
              style: const TextStyle(fontSize: 15),
              decoration: const InputDecoration(
                  labelText: 'Level',
                  prefixIcon: Icon(Icons.grade_outlined, size: 20)),
            ),
            const SizedBox(height: 20),
            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton(
                onPressed: _saving ? null : _saveProfile,
                child: _saving
                    ? const SizedBox(
                        width: 22,
                        height: 22,
                        child: CircularProgressIndicator(
                            strokeWidth: 2, color: Colors.white))
                    : const Text('Save Changes'),
              ),
            ),
          ] else ...[
            _infoRow('Full Name', _nameController.text),
            const Divider(height: 20),
            _infoRow(
                'Email',
                _emailController.text.isEmpty
                    ? 'Not set'
                    : _emailController.text),
            const Divider(height: 20),
            _infoRow(
                'Username', context.read<AuthProvider>().user?.username ?? ''),
            const Divider(height: 20),
            _infoRow(
                'Department',
                _departmentController.text.isEmpty
                    ? 'Not set'
                    : _departmentController.text),
            const Divider(height: 20),
            _infoRow(
                'Level',
                _levelController.text.isEmpty
                    ? 'Not set'
                    : '${_levelController.text} Level'),
            if (_enrollmentYear.isNotEmpty) ...[
              const Divider(height: 20),
              _infoRow('Enrollment Year', _enrollmentYear),
            ],
            if (_memberSince.isNotEmpty) ...[
              const Divider(height: 20),
              _infoRow('Member Since', _memberSince),
            ],
            if (_lastLogin.isNotEmpty) ...[
              const Divider(height: 20),
              _infoRow('Last Login', _lastLogin),
            ],
          ],
        ],
      ),
    );
  }

  Widget _buildPasswordCard() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: AppTheme.surface,
        borderRadius: BorderRadius.circular(AppTheme.radiusXl),
        border: Border.all(color: AppTheme.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            children: [
              Icon(Icons.lock_outline, size: 20, color: AppTheme.primary),
              SizedBox(width: 8),
              Text('Change Password',
                  style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: AppTheme.textPrimary)),
            ],
          ),
          const SizedBox(height: 20),
          TextFormField(
            controller: _currentPasswordController,
            obscureText: true,
            style: const TextStyle(fontSize: 15),
            decoration: const InputDecoration(
              labelText: 'Current Password',
              prefixIcon: Icon(Icons.lock_outline, size: 20),
            ),
          ),
          const SizedBox(height: 14),
          TextFormField(
            controller: _newPasswordController,
            obscureText: true,
            style: const TextStyle(fontSize: 15),
            decoration: const InputDecoration(
              labelText: 'New Password',
              prefixIcon: Icon(Icons.lock_outline, size: 20),
            ),
          ),
          const SizedBox(height: 14),
          TextFormField(
            controller: _confirmPasswordController,
            obscureText: true,
            style: const TextStyle(fontSize: 15),
            decoration: const InputDecoration(
              labelText: 'Confirm New Password',
              prefixIcon: Icon(Icons.lock_outline, size: 20),
            ),
          ),
          const SizedBox(height: 20),
          SizedBox(
            width: double.infinity,
            height: 48,
            child: ElevatedButton(
              onPressed: _changingPassword ? null : _changePassword,
              style: ElevatedButton.styleFrom(
                  backgroundColor: AppTheme.surface,
                  foregroundColor: AppTheme.primary,
                  side: BorderSide(color: AppTheme.primary.withOpacity(0.3))),
              child: _changingPassword
                  ? const SizedBox(
                      width: 22,
                      height: 22,
                      child: CircularProgressIndicator(strokeWidth: 2))
                  : const Text('Update Password'),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLogoutButton() {
    return SizedBox(
      width: double.infinity,
      height: 48,
      child: ElevatedButton.icon(
        onPressed: _logout,
        icon: const Icon(Icons.logout_rounded),
        label: const Text('Logout'),
        style: ElevatedButton.styleFrom(
          backgroundColor: AppTheme.error.withOpacity(0.1),
          foregroundColor: AppTheme.error,
          elevation: 0,
        ),
      ),
    );
  }

  Widget _infoRow(String label, String value) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label,
            style:
                const TextStyle(fontSize: 13, color: AppTheme.textSecondary)),
        Text(value,
            style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w500,
                color: AppTheme.textPrimary)),
      ],
    );
  }
}
