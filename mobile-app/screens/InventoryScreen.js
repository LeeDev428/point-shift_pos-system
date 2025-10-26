import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  TextInput,
  Modal,
  Alert,
  ActivityIndicator,
  RefreshControl,
  ScrollView,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import DateTimePicker from '@react-native-community/datetimepicker';
import productService from '../services/productService';

const InventoryScreen = ({ route, navigation }) => {
  const [products, setProducts] = useState([]);
  const [filteredProducts, setFilteredProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [filter, setFilter] = useState(route.params?.filter || 'all');
  const [modalVisible, setModalVisible] = useState(false);
  const [editMode, setEditMode] = useState(false);
  const [showDatePicker, setShowDatePicker] = useState(false);
  const [currentProduct, setCurrentProduct] = useState({
    name: '',
    sku: '',
    category_id: '1',
    price: '',
    stock_quantity: '',
    low_stock_threshold: '10',
    barcode: '',
    expiry: '',
    status: 'active',
  });

  useEffect(() => {
    loadProducts();
  }, []);

  useEffect(() => {
    filterProducts();
  }, [products, searchQuery, filter]);

  const loadProducts = async () => {
    try {
      setLoading(true);
      const data = await productService.getAll();
      setProducts(data);
    } catch (error) {
      console.error('Error loading products:', error);
      Alert.alert('Error', 'Failed to load products');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const filterProducts = () => {
    let filtered = products;

    // Search filter
    if (searchQuery) {
      filtered = filtered.filter(
        (p) =>
          p.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
          (p.barcode && p.barcode.includes(searchQuery))
      );
    }

    // Status filter
    const today = new Date();
    const sevenDaysFromNow = new Date(today);
    sevenDaysFromNow.setDate(today.getDate() + 7);

    switch (filter) {
      case 'low_stock':
        filtered = filtered.filter((p) => p.stock_quantity > 0 && p.stock_quantity <= 10);
        break;
      case 'out_of_stock':
        filtered = filtered.filter((p) => p.stock_quantity === 0);
        break;
      case 'expiring':
        filtered = filtered.filter((p) => {
          if (!p.expiry) return false;
          const expiryDate = new Date(p.expiry);
          return expiryDate > today && expiryDate <= sevenDaysFromNow;
        });
        break;
      case 'expired':
        filtered = filtered.filter((p) => {
          if (!p.expiry) return false;
          const expiryDate = new Date(p.expiry);
          return expiryDate < today;
        });
        break;
    }

    setFilteredProducts(filtered);
  };

  const onRefresh = () => {
    setRefreshing(true);
    loadProducts();
  };

  const openAddModal = () => {
    setEditMode(false);
    setCurrentProduct({
      name: '',
      sku: '',
      category_id: '1',
      price: '',
      stock_quantity: '',
      low_stock_threshold: '10',
      barcode: '',
      expiry: '',
      status: 'active',
    });
    setModalVisible(true);
  };

  const openEditModal = (product) => {
    setEditMode(true);
    setCurrentProduct({
      ...product,
      price: product.price.toString(),
      stock_quantity: product.stock_quantity.toString(),
      low_stock_threshold: product.low_stock_threshold?.toString() || '10',
      expiry: product.expiry || '',
      status: product.status || 'active',
    });
    setModalVisible(true);
  };

  const handleSave = async () => {
    try {
      if (!currentProduct.name || !currentProduct.price || !currentProduct.stock_quantity) {
        Alert.alert('Error', 'Please fill in Product Name, Price, and Quantity');
        return;
      }

      const productData = {
        ...currentProduct,
        price: parseFloat(currentProduct.price),
        stock_quantity: parseInt(currentProduct.stock_quantity),
        low_stock_threshold: parseInt(currentProduct.low_stock_threshold || 10),
        expiry: currentProduct.expiry || null,
      };

      if (editMode) {
        await productService.updateProduct(currentProduct.id, productData);
        Alert.alert('Success', 'Product updated successfully');
      } else {
        await productService.addProduct(productData);
        Alert.alert('Success', 'Product added successfully');
      }

      setModalVisible(false);
      loadProducts();
    } catch (error) {
      console.error('Error saving product:', error);
      Alert.alert('Error', 'Failed to save product: ' + error.message);
    }
  };

  const onDateChange = (event, selectedDate) => {
    setShowDatePicker(Platform.OS === 'ios');
    if (selectedDate) {
      const formattedDate = selectedDate.toISOString().split('T')[0];
      setCurrentProduct({ ...currentProduct, expiry: formattedDate });
    }
  };

  const handleDelete = (product) => {
    Alert.alert(
      'Confirm Delete',
      `Are you sure you want to delete "${product.name}"?`,
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Delete',
          style: 'destructive',
          onPress: async () => {
            try {
              await productService.deleteProduct(product.id);
              Alert.alert('Success', 'Product deleted successfully');
              loadProducts();
            } catch (error) {
              console.error('Error deleting product:', error);
              Alert.alert('Error', 'Failed to delete product');
            }
          },
        },
      ]
    );
  };

  const getStatusBadge = (product) => {
    if (product.stock_quantity === 0) {
      return { text: 'Out of Stock', color: '#dc3545' };
    } else if (product.stock_quantity <= 10) {
      return { text: 'Low Stock', color: '#ffc107' };
    }
    return { text: 'In Stock', color: '#28a745' };
  };

  const getExpiryStatus = (expiryDate) => {
    if (!expiryDate) return null;

    const today = new Date();
    const expiry = new Date(expiryDate);
    const diffTime = expiry - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays < 0) {
      return { text: 'EXPIRED', color: '#f44336', bgColor: '#ffebee' };
    } else if (diffDays <= 7) {
      return {
        text: `${diffDays} days left`,
        color: '#ff9800',
        bgColor: '#fff3e0',
      };
    }
    return null;
  };

  const renderProduct = ({ item }) => {
    const status = getStatusBadge(item);
    const expiryStatus = getExpiryStatus(item.expiry);
    const hasAlert = item.stock_quantity <= 10 || expiryStatus;

    return (
      <TouchableOpacity
        style={[
          styles.productCard,
          hasAlert && styles.productCardAlert,
        ]}
        onPress={() => openEditModal(item)}
        activeOpacity={0.7}
      >
        <View style={styles.productHeader}>
          <View style={styles.productInfo}>
            <Text style={styles.productName}>{item.name}</Text>
            {item.barcode && (
              <Text style={styles.productBarcode}>
                <Ionicons name="barcode-outline" size={14} /> {item.barcode}
              </Text>
            )}
          </View>
          <TouchableOpacity
            style={styles.deleteButton}
            onPress={() => handleDelete(item)}
          >
            <Ionicons name="trash-outline" size={20} color="#dc3545" />
          </TouchableOpacity>
        </View>

        <View style={styles.productDetails}>
          <View style={styles.detailRow}>
            <Text style={styles.detailLabel}>Price:</Text>
            <Text style={styles.detailValue}>₱{parseFloat(item.price).toFixed(2)}</Text>
          </View>
          <View style={styles.detailRow}>
            <Text style={styles.detailLabel}>Stock:</Text>
            <View style={[styles.badge, { backgroundColor: status.color }]}>
              <Text style={styles.badgeText}>{item.stock_quantity}</Text>
            </View>
          </View>
        </View>

        {item.expiry && (
          <View style={styles.expiryRow}>
            <Ionicons name="calendar-outline" size={16} color="#666" />
            <Text style={styles.expiryText}>
              Expiry: {new Date(item.expiry).toLocaleDateString()}
            </Text>
          </View>
        )}

        <View style={styles.productFooter}>
          <View style={[styles.statusBadge, { backgroundColor: status.color }]}>
            <Text style={styles.statusText}>{status.text}</Text>
          </View>

          {expiryStatus && (
            <View
              style={[
                styles.expiryBadge,
                { backgroundColor: expiryStatus.bgColor },
              ]}
            >
              <Text style={[styles.expiryBadgeText, { color: expiryStatus.color }]}>
                {expiryStatus.text}
              </Text>
            </View>
          )}
        </View>
      </TouchableOpacity>
    );
  };

  if (loading) {
    return (
      <View style={styles.centerContainer}>
        <ActivityIndicator size="large" color="#dc3545" />
        <Text style={styles.loadingText}>Loading inventory...</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {/* Search and Filter */}
      <View style={styles.searchContainer}>
        <View style={styles.searchBar}>
          <Ionicons name="search" size={20} color="#999" />
          <TextInput
            style={styles.searchInput}
            placeholder="Search products..."
            value={searchQuery}
            onChangeText={setSearchQuery}
          />
        </View>

        <TouchableOpacity style={styles.addButton} onPress={openAddModal}>
          <Ionicons name="add" size={24} color="#fff" />
        </TouchableOpacity>
      </View>

      {/* Filter Buttons */}
      <View style={styles.filterContainer}>
        <TouchableOpacity
          style={[styles.filterButton, filter === 'all' && styles.filterButtonActive]}
          onPress={() => setFilter('all')}
        >
          <Text style={[styles.filterText, filter === 'all' && styles.filterTextActive]}>
            All
          </Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={[styles.filterButton, filter === 'low_stock' && styles.filterButtonActive]}
          onPress={() => setFilter('low_stock')}
        >
          <Text style={[styles.filterText, filter === 'low_stock' && styles.filterTextActive]}>
            Low Stock
          </Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={[styles.filterButton, filter === 'out_of_stock' && styles.filterButtonActive]}
          onPress={() => setFilter('out_of_stock')}
        >
          <Text
            style={[styles.filterText, filter === 'out_of_stock' && styles.filterTextActive]}
          >
            Out of Stock
          </Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={[styles.filterButton, filter === 'expiring' && styles.filterButtonActive]}
          onPress={() => setFilter('expiring')}
        >
          <Text style={[styles.filterText, filter === 'expiring' && styles.filterTextActive]}>
            Expiring
          </Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={[styles.filterButton, filter === 'expired' && styles.filterButtonActive]}
          onPress={() => setFilter('expired')}
        >
          <Text style={[styles.filterText, filter === 'expired' && styles.filterTextActive]}>
            Expired
          </Text>
        </TouchableOpacity>
      </View>

      {/* Products List */}
      <FlatList
        data={filteredProducts}
        renderItem={renderProduct}
        keyExtractor={(item) => item.id.toString()}
        contentContainerStyle={styles.listContainer}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={['#dc3545']} />
        }
        ListEmptyComponent={
          <View style={styles.emptyContainer}>
            <Ionicons name="cube-outline" size={64} color="#ccc" />
            <Text style={styles.emptyText}>No products found</Text>
          </View>
        }
      />

      {/* Add/Edit Modal */}
      <Modal
        visible={modalVisible}
        animationType="slide"
        transparent={false}
        onRequestClose={() => setModalVisible(false)}
      >
        <KeyboardAvoidingView
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
          style={{ flex: 1 }}
        >
          <View style={styles.modalContainer}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>
                {editMode ? 'Edit Product' : 'Add New Product'}
              </Text>
              <TouchableOpacity onPress={() => setModalVisible(false)}>
                <Ionicons name="close" size={28} color="#fff" />
              </TouchableOpacity>
            </View>

            <ScrollView style={styles.modalBody} showsVerticalScrollIndicator={false}>
              <View style={styles.formRow}>
                <View style={styles.formColumn}>
                  <Text style={styles.inputLabel}>Product Name *</Text>
                  <TextInput
                    style={styles.input}
                    value={currentProduct.name}
                    onChangeText={(text) =>
                      setCurrentProduct({ ...currentProduct, name: text })
                    }
                    placeholder="Product Name"
                  />
                </View>
              </View>

              <View style={styles.formRow}>
                <View style={styles.formColumnHalf}>
                  <Text style={styles.inputLabel}>SKU</Text>
                  <TextInput
                    style={styles.input}
                    value={currentProduct.sku || ''}
                    onChangeText={(text) =>
                      setCurrentProduct({ ...currentProduct, sku: text })
                    }
                    placeholder="SKU"
                    editable={!editMode}
                  />
                </View>
                <View style={styles.formColumnHalf}>
                  <Text style={styles.inputLabel}>Category</Text>
                  <View style={styles.input}>
                    <Text style={{ color: '#999' }}>Select Category</Text>
                  </View>
                </View>
              </View>

              <View style={styles.formRow}>
                <View style={styles.formColumnHalf}>
                  <Text style={styles.inputLabel}>Price *</Text>
                  <TextInput
                    style={styles.input}
                    value={currentProduct.price.toString()}
                    onChangeText={(text) =>
                      setCurrentProduct({ ...currentProduct, price: text })
                    }
                    placeholder="Price"
                    keyboardType="decimal-pad"
                  />
                </View>
                <View style={styles.formColumnHalf}>
                  <Text style={styles.inputLabel}>Quantity *</Text>
                  <TextInput
                    style={styles.input}
                    value={currentProduct.stock_quantity.toString()}
                    onChangeText={(text) =>
                      setCurrentProduct({ ...currentProduct, stock_quantity: text })
                    }
                    placeholder="Quantity"
                    keyboardType="number-pad"
                  />
                </View>
              </View>

              <View style={styles.formRow}>
                <View style={styles.formColumnHalf}>
                  <Text style={styles.inputLabel}>Barcode</Text>
                  <TextInput
                    style={styles.input}
                    value={currentProduct.barcode || ''}
                    onChangeText={(text) =>
                      setCurrentProduct({ ...currentProduct, barcode: text })
                    }
                    placeholder="Barcode"
                  />
                </View>
                <View style={styles.formColumnHalf}>
                  <Text style={styles.inputLabel}>Low Stock Threshold</Text>
                  <TextInput
                    style={styles.input}
                    value={currentProduct.low_stock_threshold?.toString() || '10'}
                    onChangeText={(text) =>
                      setCurrentProduct({ ...currentProduct, low_stock_threshold: text })
                    }
                    placeholder="Low Stock Threshold"
                    keyboardType="number-pad"
                  />
                </View>
              </View>

              <View style={styles.formRow}>
                <View style={styles.formColumnHalf}>
                  <Text style={styles.inputLabel}>Expiry Date</Text>
                  <TouchableOpacity
                    style={styles.dateInput}
                    onPress={() => setShowDatePicker(true)}
                  >
                    <Ionicons name="calendar-outline" size={20} color="#666" />
                    <Text style={styles.dateText}>
                      {currentProduct.expiry || 'Select Date'}
                    </Text>
                  </TouchableOpacity>
                  {showDatePicker && (
                    <DateTimePicker
                      value={currentProduct.expiry ? new Date(currentProduct.expiry) : new Date()}
                      mode="date"
                      display={Platform.OS === 'ios' ? 'spinner' : 'default'}
                      onChange={onDateChange}
                      minimumDate={new Date()}
                    />
                  )}
                </View>
                <View style={styles.formColumnHalf}>
                  <Text style={styles.inputLabel}>Status</Text>
                  <View style={styles.input}>
                    <Text style={{ color: '#333' }}>
                      {currentProduct.status === 'active' ? 'Active' : 'Inactive'}
                    </Text>
                  </View>
                </View>
              </View>

              <View style={{ height: 100 }} />
            </ScrollView>

            <View style={styles.modalFooter}>
              <TouchableOpacity
                style={[styles.modalButton, styles.cancelButton]}
                onPress={() => setModalVisible(false)}
              >
                <Text style={styles.cancelButtonText}>Cancel</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.modalButton, styles.saveButton]}
                onPress={handleSave}
              >
                <Text style={styles.saveButtonText}>
                  {editMode ? 'Update Product' : 'Add Product'}
                </Text>
              </TouchableOpacity>
            </View>
          </View>
        </KeyboardAvoidingView>
      </Modal>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  centerContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#f5f5f5',
  },
  loadingText: {
    marginTop: 10,
    fontSize: 16,
    color: '#666',
  },
  searchContainer: {
    flexDirection: 'row',
    padding: 15,
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: '#e0e0e0',
  },
  searchBar: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#f5f5f5',
    borderRadius: 10,
    paddingHorizontal: 15,
    marginRight: 10,
  },
  searchInput: {
    flex: 1,
    marginLeft: 10,
    fontSize: 16,
    paddingVertical: 10,
  },
  addButton: {
    backgroundColor: '#dc3545',
    width: 50,
    height: 50,
    borderRadius: 25,
    justifyContent: 'center',
    alignItems: 'center',
  },
  filterContainer: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    backgroundColor: '#fff',
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderBottomWidth: 1,
    borderBottomColor: '#e0e0e0',
  },
  filterButton: {
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 18,
    backgroundColor: '#f5f5f5',
    marginRight: 8,
    marginBottom: 8,
  },
  filterButtonActive: {
    backgroundColor: '#dc3545',
  },
  filterText: {
    fontSize: 12,
    color: '#666',
    fontWeight: '500',
  },
  filterTextActive: {
    color: '#fff',
    fontWeight: '600',
  },
  listContainer: {
    padding: 15,
  },
  productCard: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 15,
    marginBottom: 15,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  productCardAlert: {
    borderLeftWidth: 4,
    borderLeftColor: '#ff9800',
  },
  productHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 10,
  },
  productInfo: {
    flex: 1,
  },
  productName: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 5,
  },
  productBarcode: {
    fontSize: 12,
    color: '#666',
  },
  deleteButton: {
    padding: 5,
  },
  productDetails: {
    marginBottom: 10,
  },
  detailRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 5,
  },
  detailLabel: {
    fontSize: 14,
    color: '#666',
  },
  detailValue: {
    fontSize: 16,
    fontWeight: '600',
    color: '#333',
  },
  badge: {
    paddingHorizontal: 12,
    paddingVertical: 4,
    borderRadius: 12,
  },
  badgeText: {
    color: '#fff',
    fontSize: 14,
    fontWeight: 'bold',
  },
  expiryRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 10,
  },
  expiryText: {
    fontSize: 12,
    color: '#666',
    marginLeft: 5,
  },
  productFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  statusBadge: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 15,
  },
  statusText: {
    color: '#fff',
    fontSize: 12,
    fontWeight: '600',
  },
  expiryBadge: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 15,
  },
  expiryBadgeText: {
    fontSize: 12,
    fontWeight: '600',
  },
  emptyContainer: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 60,
  },
  emptyText: {
    marginTop: 15,
    fontSize: 16,
    color: '#999',
  },
  modalContainer: {
    flex: 1,
    backgroundColor: '#fff',
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 16,
    backgroundColor: '#dc3545',
    paddingTop: 50,
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#fff',
  },
  modalBody: {
    flex: 1,
    padding: 16,
  },
  formRow: {
    flexDirection: 'row',
    marginBottom: 16,
  },
  formColumn: {
    flex: 1,
  },
  formColumnHalf: {
    flex: 1,
    marginRight: 8,
  },
  inputLabel: {
    fontSize: 13,
    fontWeight: '600',
    color: '#555',
    marginBottom: 6,
  },
  input: {
    borderWidth: 1,
    borderColor: '#ddd',
    borderRadius: 8,
    padding: 12,
    fontSize: 15,
    backgroundColor: '#fff',
    color: '#333',
  },
  dateInput: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#ddd',
    borderRadius: 8,
    padding: 12,
    backgroundColor: '#fff',
  },
  dateText: {
    fontSize: 15,
    color: '#333',
    marginLeft: 10,
  },
  textArea: {
    height: 100,
    textAlignVertical: 'top',
  },
  modalFooter: {
    flexDirection: 'row',
    padding: 20,
    borderTopWidth: 1,
    borderTopColor: '#e0e0e0',
  },
  modalButton: {
    flex: 1,
    padding: 15,
    borderRadius: 10,
    alignItems: 'center',
  },
  cancelButton: {
    backgroundColor: '#f5f5f5',
    marginRight: 10,
  },
  cancelButtonText: {
    color: '#666',
    fontSize: 16,
    fontWeight: '600',
  },
  saveButton: {
    backgroundColor: '#dc3545',
  },
  saveButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
});

export default InventoryScreen;
