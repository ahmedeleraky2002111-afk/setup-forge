class CatalogItem {
  final String id;
  final String tier;
  final String name;
  final int price;

  const CatalogItem({
    required this.id,
    required this.tier,
    required this.name,
    required this.price,
  });
}

class CartLine {
  final String type; // terminal, printer, oven, etc.
  String productId;
  String name;
  int unit;
  int qty;
  String? tier;

  CartLine({
    required this.type,
    required this.productId,
    required this.name,
    required this.unit,
    required this.qty,
    this.tier,
  });

  int get total => unit * qty;
}
