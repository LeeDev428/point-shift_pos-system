import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  RefreshControl,
  TouchableOpacity,
  ActivityIndicator,
  Alert,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import productService from '../services/productService';

const DashboardScreen = ({ navigation }) => {
  const [stats, setStats] = useState({
    totalProducts: 0,
    lowStock: 0,
    outOfStock: 0,
    expiringProducts: 0,
    expiredProducts: 0,
  });
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  useEffect(() => {
    loadDashboardData();
  }, []);

  const loadDashboardData = async () => {
    try {
      setLoading(true);
      const products = await productService.getAll();
      
      // Calculate stats
      const today = new Date();
      const sevenDaysFromNow = new Date(today);
      sevenDaysFromNow.setDate(today.getDate() + 7);
      
      const stats = {
        totalProducts: products.length,
        lowStock: products.filter(p => p.stock_quantity > 0 && p.stock_quantity <= 10).length,
        outOfStock: products.filter(p => p.stock_quantity === 0).length,
        expiringProducts: 0,
        expiredProducts: 0,
      };
      
      // Calculate expiry stats
      products.forEach(product => {
        if (product.expiry) {
          const expiryDate = new Date(product.expiry);
          if (expiryDate < today) {
            stats.expiredProducts++;
          } else if (expiryDate <= sevenDaysFromNow) {
            stats.expiringProducts++;
          }
        }
      });
      
      setStats(stats);
    } catch (error) {
      console.error('Error loading dashboard:', error);
      Alert.alert('Error', 'Failed to load dashboard data');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const onRefresh = () => {
    setRefreshing(true);
    loadDashboardData();
  };

  const StatCard = ({ title, value, icon, color, onPress }) => (
    <TouchableOpacity
      style={[styles.statCard, { borderLeftColor: color }]}
      onPress={onPress}
      activeOpacity={0.7}
    >
      <View style={styles.statContent}>
        <View style={[styles.iconContainer, { backgroundColor: color + '20' }]}>
          <Ionicons name={icon} size={30} color={color} />
        </View>
        <View style={styles.statText}>
          <Text style={styles.statValue}>{value}</Text>
          <Text style={styles.statTitle}>{title}</Text>
        </View>
      </View>
    </TouchableOpacity>
  );

  if (loading) {
    return (
      <View style={styles.centerContainer}>
        <ActivityIndicator size="large" color="#dc3545" />
        <Text style={styles.loadingText}>Loading dashboard...</Text>
      </View>
    );
  }

  return (
    <ScrollView
      style={styles.container}
      refreshControl={
        <RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={['#dc3545']} />
      }
    >
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Dashboard</Text>
        <Text style={styles.headerSubtitle}>Real-time inventory overview</Text>
      </View>

      <View style={styles.statsContainer}>
        <StatCard
          title="Total Products"
          value={stats.totalProducts}
          icon="cube-outline"
          color="#007bff"
          onPress={() => navigation.navigate('Inventory')}
        />

        <StatCard
          title="Low Stock"
          value={stats.lowStock}
          icon="alert-circle-outline"
          color="#ffc107"
          onPress={() => navigation.navigate('Inventory', { filter: 'low_stock' })}
        />

        <StatCard
          title="Out of Stock"
          value={stats.outOfStock}
          icon="close-circle-outline"
          color="#dc3545"
          onPress={() => navigation.navigate('Inventory', { filter: 'out_of_stock' })}
        />

        <StatCard
          title="Expiring Soon"
          value={stats.expiringProducts}
          icon="time-outline"
          color="#ff9800"
          onPress={() => navigation.navigate('Inventory', { filter: 'expiring' })}
        />

        <StatCard
          title="Expired"
          value={stats.expiredProducts}
          icon="warning-outline"
          color="#f44336"
          onPress={() => navigation.navigate('Inventory', { filter: 'expired' })}
        />
      </View>

      {/* Quick Actions */}
      <View style={styles.quickActionsContainer}>
        <Text style={styles.sectionTitle}>Quick Actions</Text>
        
        <TouchableOpacity
          style={styles.actionButton}
          onPress={() => navigation.navigate('Scanner')}
        >
          <Ionicons name="barcode-outline" size={24} color="#fff" />
          <Text style={styles.actionButtonText}>Scan Barcode</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={[styles.actionButton, { backgroundColor: '#28a745' }]}
          onPress={() => navigation.navigate('Inventory', { action: 'add' })}
        >
          <Ionicons name="add-circle-outline" size={24} color="#fff" />
          <Text style={styles.actionButtonText}>Add Product</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={[styles.actionButton, { backgroundColor: '#17a2b8' }]}
          onPress={() => navigation.navigate('Inventory')}
        >
          <Ionicons name="list-outline" size={24} color="#fff" />
          <Text style={styles.actionButtonText}>View Inventory</Text>
        </TouchableOpacity>
      </View>

      {/* Alerts Section */}
      {(stats.expiredProducts > 0 || stats.expiringProducts > 0 || stats.outOfStock > 0) && (
        <View style={styles.alertsContainer}>
          <Text style={styles.sectionTitle}>Alerts</Text>
          
          {stats.expiredProducts > 0 && (
            <View style={[styles.alertCard, styles.alertDanger]}>
              <Ionicons name="warning" size={20} color="#f44336" />
              <Text style={styles.alertText}>
                {stats.expiredProducts} product(s) have expired!
              </Text>
            </View>
          )}

          {stats.expiringProducts > 0 && (
            <View style={[styles.alertCard, styles.alertWarning]}>
              <Ionicons name="time" size={20} color="#ff9800" />
              <Text style={styles.alertText}>
                {stats.expiringProducts} product(s) expiring within 7 days
              </Text>
            </View>
          )}

          {stats.outOfStock > 0 && (
            <View style={[styles.alertCard, styles.alertInfo]}>
              <Ionicons name="alert-circle" size={20} color="#dc3545" />
              <Text style={styles.alertText}>
                {stats.outOfStock} product(s) are out of stock
              </Text>
            </View>
          )}
        </View>
      )}
    </ScrollView>
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
  header: {
    backgroundColor: '#dc3545',
    padding: 20,
    paddingTop: 60,
    paddingBottom: 30,
  },
  headerTitle: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#fff',
    marginBottom: 5,
  },
  headerSubtitle: {
    fontSize: 14,
    color: '#fff',
    opacity: 0.9,
  },
  statsContainer: {
    padding: 15,
  },
  statCard: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 15,
    marginBottom: 15,
    borderLeftWidth: 4,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  statContent: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  iconContainer: {
    width: 60,
    height: 60,
    borderRadius: 30,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 15,
  },
  statText: {
    flex: 1,
  },
  statValue: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#333',
  },
  statTitle: {
    fontSize: 16,
    color: '#666',
    marginTop: 5,
  },
  quickActionsContainer: {
    padding: 15,
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 15,
  },
  actionButton: {
    backgroundColor: '#dc3545',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 15,
    borderRadius: 10,
    marginBottom: 10,
  },
  actionButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
    marginLeft: 10,
  },
  alertsContainer: {
    padding: 15,
  },
  alertCard: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 15,
    borderRadius: 10,
    marginBottom: 10,
  },
  alertDanger: {
    backgroundColor: '#ffebee',
  },
  alertWarning: {
    backgroundColor: '#fff3e0',
  },
  alertInfo: {
    backgroundColor: '#ffebee',
  },
  alertText: {
    marginLeft: 10,
    fontSize: 14,
    color: '#333',
    flex: 1,
  },
});

export default DashboardScreen;
