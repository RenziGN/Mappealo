import AsyncStorage from '@react-native-async-storage/async-storage';
import { initializeApp } from 'firebase/app';
import { getReactNativePersistence, initializeAuth } from 'firebase/auth';

//CONFIGURACIÓN DE FIREBASE
const firebaseConfig = {
  apiKey: "AIzaSyDwB96b2oLha1YeApssq3gIiL3lrQrh5Gg",
  authDomain: "mappealo-42853.firebaseapp.com",
  projectId: "mappealo-42853",
  storageBucket: "mappealo-42853.appspot.com",
  messagingSenderId: "157855927504",
  appId: "1:157855927504:web:13bde13c2f0363f38d22d5",
  measurementId: "G-YQ1KRDFP34"
};

// INICIACIÓN DE FIREBASE
const app = initializeApp(firebaseConfig);

// AUTENTICACIÓN
const auth = initializeAuth(app, {
  persistence: getReactNativePersistence(AsyncStorage)
});

export { auth };

