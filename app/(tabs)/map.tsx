import React, { useState } from 'react';
import { StyleSheet, View } from 'react-native';
import MapView, { Marker } from 'react-native-maps';

export default function App() {
  const [marker, setMarker] = useState<any>(null);

  return (
    <View style={styles.container}>
      <MapView
        style={styles.map}
        initialRegion={{
          latitude: -34.6037,
          longitude: -58.3816,
          latitudeDelta: 0.05,
          longitudeDelta: 0.05,
        }}
        onPress={(e) => {
          setMarker(e.nativeEvent.coordinate);
        }}
      >
        {marker && (
          <Marker
            coordinate={marker}
            draggable
            title="Ubicación elegida"
          />
        )}
      </MapView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  map: { flex: 1 },
});