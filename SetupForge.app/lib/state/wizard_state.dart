import 'package:flutter/foundation.dart';

class WizardState extends ChangeNotifier {
  String businessType = '';
  String businessName = '';
  String placeSize = '';
  double budget = 0;

  List<String> selectedModules = [];
  Map<String, String> moduleTiers = {};
  String posAddOn = '';
  Map<String, int> staffCounts = {'baristas': 0, 'cashiers': 0, 'waiters': 0};

  List<Map<String, dynamic>> cartItems = [];

  double get totalPrice {
    double total = 0;
    for (final item in cartItems) {
      final price = (item['price'] as num?)?.toDouble() ?? 0;
      final qty = (item['qty'] as num?)?.toInt() ?? 1;
      total += price * qty;
    }
    return total;
  }

  void setBusinessType(String value) {
    businessType = value;
    notifyListeners();
  }

  void setBusinessName(String value) {
    businessName = value;
    notifyListeners();
  }

  void setPlaceSize(String value) {
    placeSize = value;
    notifyListeners();
  }

  void setBudget(double value) {
    budget = value;
    notifyListeners();
  }

  void setModules(List<String> modules) {
    selectedModules = modules;
    notifyListeners();
  }

  void setModuleTiers(Map<String, String> tiers) {
    moduleTiers = Map<String, String>.from(tiers);
    notifyListeners();
  }

  void setPosAddOn(String value) {
    posAddOn = value;
    notifyListeners();
  }

  void setStaffCounts(Map<String, int> counts) {
    staffCounts = Map<String, int>.from(counts);
    notifyListeners();
  }

  void setCartItems(List<Map<String, dynamic>> items) {
    cartItems = List<Map<String, dynamic>>.from(items);
    notifyListeners();
  }

  void addCartItem(Map<String, dynamic> item) {
    cartItems.add(item);
    notifyListeners();
  }

  void updateCartItemQty(String keyId, int qty) {
    final index = cartItems.indexWhere((item) => item['keyId'] == keyId);
    if (index != -1) {
      cartItems[index]['qty'] = qty;
      notifyListeners();
    }
  }

  void clearCart() {
    cartItems.clear();
    notifyListeners();
  }

  void saveSetup({
    required String businessType,
    required String businessName,
    required String placeSize,
    required double budget,
    required List<String> selectedModules,
    required Map<String, String> moduleTiers,
    required String posAddOn,
    required Map<String, int> staffCounts,
  }) {
    this.businessType = businessType;
    this.businessName = businessName;
    this.placeSize = placeSize;
    this.budget = budget;
    this.selectedModules = List<String>.from(selectedModules);
    this.moduleTiers = Map<String, String>.from(moduleTiers);
    this.posAddOn = posAddOn;
    this.staffCounts = Map<String, int>.from(staffCounts);
    notifyListeners();
  }

  void resetAll() {
    businessType = '';
    businessName = '';
    placeSize = '';
    budget = 0;
    selectedModules = [];
    moduleTiers = {};
    posAddOn = '';
    staffCounts = {'baristas': 0, 'cashiers': 0, 'waiters': 0};
    cartItems = [];
    notifyListeners();
  }
}
