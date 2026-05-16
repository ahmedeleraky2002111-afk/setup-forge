import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:video_player/video_player.dart';

import '../state/wizard_state.dart';

class SetupScreen extends StatefulWidget {
  const SetupScreen({super.key});

  @override
  State<SetupScreen> createState() => _SetupScreenState();
}

class _SetupScreenState extends State<SetupScreen> {
  static const Color sfBlue = Color(0xFF004CAC);
  // ignore: unused_field
  static const Color sfTeal = Color(0xFF009994);
  static const Color bg = Color(0xFFF5F7FB);
  static const Color text = Color(0xFF121212);
  static const Color muted = Color(0xFF6C757D);
  static const Color border = Color(0x22000000);

  int step = 0;

  late TextEditingController nameController;
  late TextEditingController budgetController;

  late final Map<String, VideoPlayerController> _videoControllers;

  final List<_BusinessOption> businessOptions = const [
    _BusinessOption(
      title: 'Restaurant',
      videoPath: 'assets/restaurant.mp4',
      icon: Icons.restaurant_rounded,
    ),
    _BusinessOption(
      title: 'Cafe',
      videoPath: 'assets/cafe.mp4',
      icon: Icons.local_cafe_rounded,
    ),
    _BusinessOption(
      title: 'Gym',
      videoPath: 'assets/gym.mp4',
      icon: Icons.fitness_center_rounded,
    ),
    _BusinessOption(
      title: 'Office',
      videoPath: 'assets/office.mp4',
      icon: Icons.business_center_rounded,
    ),
  ];

  final List<_SizeOption> sizeOptions = const [
    _SizeOption(
      title: 'Small',
      imagePath: 'assets/size_small.png',
      subtitle: 'Compact and efficient starter setup',
    ),
    _SizeOption(
      title: 'Medium',
      imagePath: 'assets/size_medium.png',
      subtitle: 'Balanced setup for growing businesses',
    ),
    _SizeOption(
      title: 'Large',
      imagePath: 'assets/size_large.png',
      subtitle: 'Bigger setup with wider operational needs',
    ),
  ];

  final List<String> modules = ['kitchen', 'furniture', 'pos', 'electronics'];

  @override
  void initState() {
    super.initState();

    final wizard = Provider.of<WizardState>(context, listen: false);

    nameController = TextEditingController(text: wizard.businessName);
    budgetController = TextEditingController(
      text: wizard.budget == 0 ? '' : wizard.budget.toStringAsFixed(0),
    );

    _videoControllers = {
      for (final option in businessOptions)
        option.title: VideoPlayerController.asset(option.videoPath),
    };

    _initializeVideos();
  }

  Future<void> _initializeVideos() async {
    for (final controller in _videoControllers.values) {
      await controller.initialize();
      await controller.setLooping(true);
      await controller.setVolume(0);
      await controller.play();
    }

    if (mounted) setState(() {});
  }

  @override
  void dispose() {
    nameController.dispose();
    budgetController.dispose();

    for (final controller in _videoControllers.values) {
      controller.dispose();
    }

    super.dispose();
  }

  void _nextStep(WizardState wizard) {
    if (step == 1) {
      wizard.setBusinessName(nameController.text.trim());
    }

    if (step == 3) {
      wizard.setBudget(double.tryParse(budgetController.text.trim()) ?? 0);
    }

    if (step < 5) {
      setState(() => step++);
    } else {
      Navigator.pushNamed(context, '/packages');
    }
  }

  void _prevStep() {
    if (step > 0) {
      setState(() => step--);
    } else {
      Navigator.pop(context);
    }
  }

  bool _canContinue(WizardState wizard) {
    switch (step) {
      case 0:
        return wizard.businessType.isNotEmpty;
      case 1:
        return nameController.text.trim().isNotEmpty;
      case 2:
        return wizard.placeSize.isNotEmpty;
      case 3:
        return (double.tryParse(budgetController.text.trim()) ?? 0) > 0;
      case 4:
        return wizard.selectedModules.isNotEmpty;
      case 5:
        return true;
      default:
        return true;
    }
  }

  void _toggleModule(WizardState wizard, String module, bool selected) {
    final updated = List<String>.from(wizard.selectedModules);

    if (selected) {
      if (!updated.contains(module)) updated.add(module);
    } else {
      updated.remove(module);
    }

    wizard.setModules(updated);

    final updatedTiers = Map<String, String>.from(wizard.moduleTiers);
    if (!selected) {
      updatedTiers.remove(module);
    } else {
      updatedTiers.putIfAbsent(module, () => 'Standard');
    }
    wizard.setModuleTiers(updatedTiers);
  }

  void _setModuleTier(WizardState wizard, String module, String tier) {
    final updatedTiers = Map<String, String>.from(wizard.moduleTiers);
    updatedTiers[module] = tier;
    wizard.setModuleTiers(updatedTiers);
  }

  @override
  Widget build(BuildContext context) {
    final wizard = context.watch<WizardState>();

    return Scaffold(
      backgroundColor: bg,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        foregroundColor: text,
        title: const Text(
          'My Setup',
          style: TextStyle(fontWeight: FontWeight.w900),
        ),
        leading: IconButton(
          onPressed: _prevStep,
          icon: const Icon(Icons.arrow_back_rounded),
        ),
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 4, 16, 16),
          child: Column(
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(999),
                child: LinearProgressIndicator(
                  minHeight: 8,
                  value: (step + 1) / 6,
                  backgroundColor: Colors.grey.shade300,
                  valueColor: const AlwaysStoppedAnimation(sfBlue),
                ),
              ),
              const SizedBox(height: 12),
              Align(
                alignment: Alignment.centerLeft,
                child: Text(
                  'Step ${step + 1} of 6',
                  style: const TextStyle(
                    fontSize: 12.5,
                    fontWeight: FontWeight.w700,
                    color: muted,
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Expanded(
                child: SingleChildScrollView(child: _buildStepContent(wizard)),
              ),
              const SizedBox(height: 14),
              Row(
                children: [
                  if (step > 0)
                    Expanded(
                      child: OutlinedButton(
                        onPressed: _prevStep,
                        style: OutlinedButton.styleFrom(
                          minimumSize: const Size.fromHeight(54),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(18),
                          ),
                        ),
                        child: const Text(
                          'Back',
                          style: TextStyle(fontWeight: FontWeight.w800),
                        ),
                      ),
                    ),
                  if (step > 0) const SizedBox(width: 12),
                  Expanded(
                    child: ElevatedButton(
                      onPressed: _canContinue(wizard)
                          ? () => _nextStep(wizard)
                          : null,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: sfBlue,
                        minimumSize: const Size.fromHeight(56),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(18),
                        ),
                      ),
                      child: Text(
                        step == 5 ? 'Generate Packages' : 'Next',
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.w900,
                          fontSize: 16,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStepContent(WizardState wizard) {
    switch (step) {
      case 0:
        return _sectionCard(
          title: 'What business are you opening?',
          subtitle: 'Choose the business type that best matches your setup.',
          child: GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: businessOptions.length,
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2,
              mainAxisSpacing: 12,
              crossAxisSpacing: 12,
              childAspectRatio: 0.92,
            ),
            itemBuilder: (context, index) {
              final option = businessOptions[index];
              final selected = wizard.businessType == option.title;
              final controller = _videoControllers[option.title]!;

              return AnimatedContainer(
                duration: const Duration(milliseconds: 220),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(22),
                  border: Border.all(
                    color: selected ? sfBlue : border,
                    width: selected ? 1.6 : 1,
                  ),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(selected ? 0.07 : 0.03),
                      blurRadius: selected ? 16 : 10,
                      offset: const Offset(0, 8),
                    ),
                  ],
                ),
                child: InkWell(
                  borderRadius: BorderRadius.circular(22),
                  onTap: () => wizard.setBusinessType(option.title),
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(22),
                    child: Stack(
                      fit: StackFit.expand,
                      children: [
                        if (controller.value.isInitialized)
                          FittedBox(
                            fit: BoxFit.cover,
                            child: SizedBox(
                              width: controller.value.size.width,
                              height: controller.value.size.height,
                              child: VideoPlayer(controller),
                            ),
                          )
                        else
                          Container(color: Colors.grey.shade200),
                        Container(
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              begin: Alignment.bottomCenter,
                              end: Alignment.topCenter,
                              colors: [
                                Colors.black.withOpacity(0.58),
                                Colors.black.withOpacity(0.08),
                              ],
                            ),
                          ),
                        ),
                        if (selected)
                          Positioned(
                            top: 12,
                            right: 12,
                            child: Container(
                              width: 34,
                              height: 34,
                              decoration: const BoxDecoration(
                                color: Colors.white,
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(
                                Icons.check_rounded,
                                color: sfBlue,
                                size: 20,
                              ),
                            ),
                          ),
                        Positioned(
                          left: 14,
                          right: 14,
                          bottom: 14,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Icon(option.icon, color: Colors.white, size: 22),
                              const SizedBox(height: 8),
                              Text(
                                option.title,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 17,
                                  fontWeight: FontWeight.w900,
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
            },
          ),
        );

      case 1:
        return _sectionCard(
          title: 'What is your business name?',
          subtitle: 'This helps personalize your setup journey.',
          child: TextField(
            controller: nameController,
            onChanged: (_) => setState(() {}),
            decoration: InputDecoration(
              hintText: 'Enter business name',
              filled: true,
              fillColor: const Color(0xFFF8FAFF),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(18),
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(18),
                borderSide: const BorderSide(color: border),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(18),
                borderSide: const BorderSide(color: sfBlue, width: 1.4),
              ),
            ),
          ),
        );

      case 2:
        return _sectionCard(
          title: 'Choose your business size',
          subtitle: 'Select the size that best matches your project.',
          child: Column(
            children: sizeOptions.map((option) {
              final selected = wizard.placeSize == option.title;

              return GestureDetector(
                onTap: () => wizard.setPlaceSize(option.title),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 220),
                  margin: const EdgeInsets.only(bottom: 14),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(22),
                    border: Border.all(
                      color: selected ? sfBlue : border,
                      width: selected ? 1.6 : 1,
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(selected ? 0.06 : 0.03),
                        blurRadius: selected ? 14 : 8,
                        offset: const Offset(0, 6),
                      ),
                    ],
                  ),
                  child: Row(
                    children: [
                      ClipRRect(
                        borderRadius: const BorderRadius.only(
                          topLeft: Radius.circular(22),
                          bottomLeft: Radius.circular(22),
                        ),
                        child: Image.asset(
                          option.imagePath,
                          width: 110,
                          height: 100,
                          fit: BoxFit.cover,
                        ),
                      ),
                      Expanded(
                        child: Padding(
                          padding: const EdgeInsets.all(14),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  Expanded(
                                    child: Text(
                                      option.title,
                                      style: TextStyle(
                                        fontSize: 17,
                                        fontWeight: FontWeight.w900,
                                        color: selected ? sfBlue : text,
                                      ),
                                    ),
                                  ),
                                  if (selected)
                                    const Icon(
                                      Icons.check_circle_rounded,
                                      color: sfBlue,
                                    ),
                                ],
                              ),
                              const SizedBox(height: 8),
                              Text(
                                option.subtitle,
                                style: const TextStyle(
                                  fontSize: 13,
                                  height: 1.4,
                                  color: muted,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              );
            }).toList(),
          ),
        );

      case 3:
        return _sectionCard(
          title: 'What is your estimated budget?',
          subtitle: 'This helps generate realistic recommendations.',
          child: TextField(
            controller: budgetController,
            keyboardType: TextInputType.number,
            onChanged: (_) => setState(() {}),
            decoration: InputDecoration(
              hintText: 'Enter budget',
              prefixText: 'EGP ',
              filled: true,
              fillColor: const Color(0xFFF8FAFF),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(18),
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(18),
                borderSide: const BorderSide(color: border),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(18),
                borderSide: const BorderSide(color: sfBlue, width: 1.4),
              ),
            ),
          ),
        );

      case 4:
        return _sectionCard(
          title: 'Select your modules',
          subtitle: 'Choose what you want included in your setup.',
          child: Column(
            children: modules.map((module) {
              final selected = wizard.selectedModules.contains(module);

              return Container(
                margin: const EdgeInsets.only(bottom: 12),
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(18),
                  border: Border.all(
                    color: selected ? sfBlue : border,
                    width: selected ? 1.4 : 1,
                  ),
                ),
                child: Column(
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            module[0].toUpperCase() + module.substring(1),
                            style: const TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.w800,
                              color: text,
                            ),
                          ),
                        ),
                        Switch(
                          value: selected,
                          activeThumbColor: sfBlue,
                          onChanged: (val) {
                            _toggleModule(wizard, module, val);
                          },
                        ),
                      ],
                    ),
                    if (selected) ...[
                      const SizedBox(height: 10),
                      DropdownButtonFormField<String>(
                        initialValue: wizard.moduleTiers[module] ?? 'Standard',
                        items: const [
                          DropdownMenuItem(
                            value: 'Budget',
                            child: Text('Budget'),
                          ),
                          DropdownMenuItem(
                            value: 'Standard',
                            child: Text('Standard'),
                          ),
                          DropdownMenuItem(
                            value: 'Premium',
                            child: Text('Premium'),
                          ),
                        ],
                        onChanged: (val) {
                          if (val != null) {
                            _setModuleTier(wizard, module, val);
                          }
                        },
                        decoration: InputDecoration(
                          hintText: 'Select tier',
                          filled: true,
                          fillColor: const Color(0xFFF8FAFF),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(14),
                          ),
                          enabledBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(14),
                            borderSide: const BorderSide(color: border),
                          ),
                        ),
                      ),
                    ],
                  ],
                ),
              );
            }).toList(),
          ),
        );

      case 5:
        return _sectionCard(
          title: 'Review your setup',
          subtitle:
              'Make sure everything looks right before generating packages.',
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _reviewRow('Business Type', wizard.businessType),
              _reviewRow('Business Name', wizard.businessName),
              _reviewRow('Size', wizard.placeSize),
              _reviewRow(
                'Budget',
                wizard.budget > 0
                    ? 'EGP ${wizard.budget.toStringAsFixed(0)}'
                    : '',
              ),
              _reviewRow(
                'Modules',
                wizard.selectedModules.isEmpty
                    ? ''
                    : wizard.selectedModules.join(', '),
              ),
            ],
          ),
        );

      default:
        return const SizedBox.shrink();
    }
  }

  Widget _sectionCard({
    required String title,
    required String subtitle,
    required Widget child,
  }) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 16,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.w900,
              color: text,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            subtitle,
            style: const TextStyle(
              fontSize: 13.5,
              height: 1.45,
              color: muted,
              fontWeight: FontWeight.w500,
            ),
          ),
          const SizedBox(height: 20),
          child,
        ],
      ),
    );
  }

  Widget _reviewRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 115,
            child: Text(
              label,
              style: const TextStyle(fontWeight: FontWeight.w800, color: text),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              value.isEmpty ? '-' : value,
              style: const TextStyle(
                color: muted,
                height: 1.4,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _BusinessOption {
  final String title;
  final String videoPath;
  final IconData icon;

  const _BusinessOption({
    required this.title,
    required this.videoPath,
    required this.icon,
  });
}

class _SizeOption {
  final String title;
  final String imagePath;
  final String subtitle;

  const _SizeOption({
    required this.title,
    required this.imagePath,
    required this.subtitle,
  });
}
