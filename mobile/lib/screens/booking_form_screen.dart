import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/booking_provider.dart';
import '../services/api_service.dart';
import '../theme.dart';

class BookingFormScreen extends StatefulWidget {
  const BookingFormScreen({super.key});

  @override
  State<BookingFormScreen> createState() => _BookingFormScreenState();
}

class _BookingFormScreenState extends State<BookingFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _dateController = TextEditingController();
  final _startTimeController = TextEditingController();
  final _endTimeController = TextEditingController();
  final _purposeController = TextEditingController();
  List<dynamic> _resources = [];
  int? _selectedResourceId;
  bool _loadingResources = true;

  @override
  void initState() {
    super.initState();
    _loadResources();
  }

  @override
  void dispose() {
    _dateController.dispose();
    _startTimeController.dispose();
    _endTimeController.dispose();
    _purposeController.dispose();
    super.dispose();
  }

  Future<void> _loadResources() async {
    try {
      final response = await ApiService.getResources();
      if (response['success']) {
        setState(() {
          _resources = response['data'] as List;
          _loadingResources = false;
        });
      }
    } catch (_) {
      setState(() => _loadingResources = false);
    }
  }

  Future<void> _pickDate() async {
    final date = await showDatePicker(
      context: context,
      initialDate: DateTime.now().add(const Duration(days: 1)),
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 60)),
      builder: (context, child) => Theme(
        data: Theme.of(context).copyWith(
          colorScheme:
              Theme.of(context).colorScheme.copyWith(primary: AppTheme.primary),
        ),
        child: child!,
      ),
    );
    if (date != null) {
      _dateController.text =
          '${date.year}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';
    }
  }

  Future<void> _pickTime(TextEditingController controller) async {
    final time = await showTimePicker(
      context: context,
      initialTime: const TimeOfDay(hour: 8, minute: 0),
      builder: (context, child) => Theme(
        data: Theme.of(context).copyWith(
          colorScheme:
              Theme.of(context).colorScheme.copyWith(primary: AppTheme.primary),
        ),
        child: child!,
      ),
    );
    if (time != null) {
      controller.text =
          '${time.hour.toString().padLeft(2, '0')}:${time.minute.toString().padLeft(2, '0')}:00';
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate() || _selectedResourceId == null) {
      return;
    }

    final provider = context.read<BookingProvider>();
    final success = await provider.submitBooking(
      _selectedResourceId!,
      _dateController.text.trim(),
      _startTimeController.text.trim(),
      _endTimeController.text.trim(),
      _purposeController.text.trim(),
    );

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
              success ? 'Booking submitted!' : (provider.error ?? 'Failed')),
          backgroundColor: success ? AppTheme.secondary : AppTheme.error,
        ),
      );
      if (success) Navigator.of(context).pop();
    }
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<BookingProvider>();

    return Scaffold(
      appBar: AppBar(title: const Text('New Booking')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: AppTheme.surface,
                  borderRadius: BorderRadius.circular(AppTheme.radiusXl),
                  border: Border.all(color: AppTheme.border),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Booking Details',
                        style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: AppTheme.textPrimary)),
                    const SizedBox(height: 20),
                    if (_loadingResources)
                      const Center(child: CircularProgressIndicator())
                    else
                      DropdownButtonFormField<int>(
                        value: _selectedResourceId,
                        decoration: const InputDecoration(
                          labelText: 'Resource',
                          prefixIcon:
                              Icon(Icons.meeting_room_outlined, size: 20),
                        ),
                        items: _resources
                            .map<DropdownMenuItem<int>>(
                                (r) => DropdownMenuItem<int>(
                                      value: r['resource_id'] as int,
                                      child: Text(
                                          '${r['resource_name']} (${r['resource_type']})'),
                                    ))
                            .toList(),
                        onChanged: (v) =>
                            setState(() => _selectedResourceId = v),
                        validator: (v) =>
                            v == null ? 'Select a resource' : null,
                        style: const TextStyle(
                            fontSize: 14, color: AppTheme.textPrimary),
                      ),
                    const SizedBox(height: 14),
                    TextFormField(
                      controller: _dateController,
                      readOnly: true,
                      onTap: _pickDate,
                      validator: (v) =>
                          (v == null || v.isEmpty) ? 'Select a date' : null,
                      style: const TextStyle(fontSize: 15),
                      decoration: const InputDecoration(
                        labelText: 'Date',
                        prefixIcon: Icon(Icons.calendar_today, size: 20),
                        suffixIcon: Icon(Icons.arrow_drop_down),
                      ),
                    ),
                    const SizedBox(height: 14),
                    Row(
                      children: [
                        Expanded(
                          child: TextFormField(
                            controller: _startTimeController,
                            readOnly: true,
                            onTap: () => _pickTime(_startTimeController),
                            validator: (v) =>
                                (v == null || v.isEmpty) ? 'Required' : null,
                            style: const TextStyle(fontSize: 15),
                            decoration: const InputDecoration(
                              labelText: 'Start',
                              prefixIcon: Icon(Icons.schedule, size: 20),
                              suffixIcon: Icon(Icons.arrow_drop_down),
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: TextFormField(
                            controller: _endTimeController,
                            readOnly: true,
                            onTap: () => _pickTime(_endTimeController),
                            validator: (v) =>
                                (v == null || v.isEmpty) ? 'Required' : null,
                            style: const TextStyle(fontSize: 15),
                            decoration: const InputDecoration(
                              labelText: 'End',
                              prefixIcon: Icon(Icons.schedule, size: 20),
                              suffixIcon: Icon(Icons.arrow_drop_down),
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 14),
                    TextFormField(
                      controller: _purposeController,
                      maxLines: 3,
                      validator: (v) =>
                          (v == null || v.isEmpty) ? 'Enter a purpose' : null,
                      style: const TextStyle(fontSize: 15),
                      decoration: const InputDecoration(
                        labelText: 'Purpose',
                        prefixIcon: Icon(Icons.description_outlined, size: 20),
                        alignLabelWithHint: true,
                      ),
                    ),
                    const SizedBox(height: 24),
                    SizedBox(
                      width: double.infinity,
                      height: 52,
                      child: ElevatedButton(
                        onPressed: provider.isLoading ? null : _submit,
                        child: provider.isLoading
                            ? const SizedBox(
                                width: 22,
                                height: 22,
                                child: CircularProgressIndicator(
                                    strokeWidth: 2, color: Colors.white))
                            : const Text('Submit Booking'),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
