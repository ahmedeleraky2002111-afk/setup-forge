import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  static const String baseUrl = "http://10.128.238.67/setupforge";

  Future<void> saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString("token", token);
  }

  Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString("token");
  }

  Future<void> clearToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove("token");
  }

  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
  }) async {
    final uri = Uri.parse("$baseUrl/auth/api_login.php");

    final res = await http.post(
      uri,
      body: {"email": email, "password": password},
    );

    final data = jsonDecode(res.body) as Map<String, dynamic>;

    if (data["ok"] == true && data["token"] != null) {
      await saveToken(data["token"].toString());
    }

    return data;
  }

  Future<Map<String, dynamic>> signup({
    required String name,
    required String email,
    required String password,
  }) async {
    final uri = Uri.parse("$baseUrl/auth/api_signup.php");

    final res = await http.post(
      uri,
      body: {"name": name, "email": email, "password": password},
    );

    final data = jsonDecode(res.body) as Map<String, dynamic>;

    if (data["ok"] == true && data["token"] != null) {
      await saveToken(data["token"].toString());
    }

    return data;
  }

  Future<Map<String, dynamic>> signupFull({
    required String name,
    required String email,
    required String password,
    String? phone,
    String? country,
    String? city,
    String? street,
    String? businessType,
    String? size,
    int? budget,
  }) async {
    final uri = Uri.parse("$baseUrl/auth/api_signup.php");

    final body = <String, String>{
      "name": name,
      "email": email,
      "password": password,
    };

    if (phone != null && phone.trim().isNotEmpty) body["phone"] = phone.trim();
    if (country != null && country.trim().isNotEmpty) {
      body["country"] = country.trim();
    }
    if (city != null && city.trim().isNotEmpty) body["city"] = city.trim();
    if (street != null && street.trim().isNotEmpty) {
      body["street"] = street.trim();
    }
    if (businessType != null && businessType.trim().isNotEmpty) {
      body["business_type"] = businessType.trim();
    }
    if (size != null && size.trim().isNotEmpty) body["size"] = size.trim();
    if (budget != null && budget > 0) body["budget"] = budget.toString();

    final res = await http.post(uri, body: body);

    Map<String, dynamic> data;
    try {
      data = jsonDecode(res.body) as Map<String, dynamic>;
    } catch (_) {
      return {"ok": false, "error": "Invalid server response", "raw": res.body};
    }

    if (data["ok"] == true && data["token"] != null) {
      await saveToken(data["token"].toString());
    }

    return data;
  }

  Future<Map<String, dynamic>> me() async {
    final token = await getToken();
    if (token == null || token.isEmpty) {
      return {"ok": false, "error": "No token"};
    }

    final uri = Uri.parse("$baseUrl/auth/api_me.php");

    final res = await http.get(
      uri,
      headers: {"Authorization": "Bearer $token"},
    );

    return jsonDecode(res.body) as Map<String, dynamic>;
  }

  Future<void> logout() async {
    await clearToken();
  }

  Future<Map<String, dynamic>> placeOrder({
    required String businessName,
    required String phone,
    required String address,
    String notes = '',
    String businessType = '',
    String size = '',
    double budget = 0,
    String preferredDeliveryDate = '',
    required List<Map<String, dynamic>> items,
  }) async {
    final token = await getToken();

    if (token == null || token.isEmpty) {
      return {"ok": false, "error": "No token found. Please login first."};
    }

    final uri = Uri.parse("$baseUrl/auth/api_place_order.php");

    final payload = {
      "business_name": businessName,
      "phone": phone,
      "address": address,
      "notes": notes,
      "business_type": businessType,
      "size": size,
      "budget": budget,
      "preferred_delivery_date": preferredDeliveryDate,
      "items": items,
    };

    final res = await http.post(
      uri,
      headers: {
        "Content-Type": "application/json",
        "Authorization": "Bearer $token",
      },
      body: jsonEncode(payload),
    );

    Map<String, dynamic> data;
    try {
      data = jsonDecode(res.body) as Map<String, dynamic>;
    } catch (_) {
      return {
        "ok": false,
        "error": "Invalid server response",
        "raw": res.body,
        "status_code": res.statusCode,
      };
    }

    return data;
  }

  Future<Map<String, dynamic>> generatePackages({
    required String businessType,
    required String size,
    required int budget,
    required List<String> modules,
    required Map<String, String> moduleTiers,
    String restaurantType = 'standard_dining',
  }) async {
    final uri = Uri.parse("$baseUrl/auth/api_generate_packages.php");

    final safeModules = modules
        .map((e) => e.trim().toLowerCase())
        .where((e) => e == "kitchen" || e == "furniture" || e == "pos")
        .toSet()
        .toList();

    final payload = {
      "business_type": businessType,
      "size": size,
      "budget": budget,
      "modules": safeModules,
      "module_tiers": moduleTiers,
      "restaurant_type": restaurantType,
    };

    try {
      final res = await http.post(
        uri,
        headers: {"Content-Type": "application/json"},
        body: jsonEncode(payload),
      );

      print("URL: $uri");
      print("Status: ${res.statusCode}");
      print("Body: ${res.body}");

      try {
        return jsonDecode(res.body) as Map<String, dynamic>;
      } catch (_) {
        return {
          "ok": false,
          "error": "Invalid server response",
          "raw": res.body,
          "status_code": res.statusCode,
        };
      }
    } catch (e) {
      return {"ok": false, "error": "Request failed: $e"};
    }
  }
}
