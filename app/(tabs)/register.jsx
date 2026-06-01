// app/register.jsx
import { useRouter } from 'expo-router'; // Para manejar la navegación
import { useState } from 'react';
import { Alert, Image, Text, TouchableOpacity, View } from 'react-native';

// Reutilizamos tus componentes modulares y tus estilos base
import { CustomButton } from '../../src/components/CustomButton';
import { CustomInput } from '../../src/components/CustomInput';
import { loginStyles } from '../../src/styles/LoginStyles';

// Importamos la función lógica que modificamos en el authService
import { registerWithEmail } from '../../src/services/AuthService';

export default function RegisterScreen() {
  const router = useRouter();

  // Estados para capturar los datos de los 4 inputs del formulario
  const [fullName, setFullName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  
  // Estados para controlar la visibilidad de las contraseñas independientemente
  const [secureText, setSecureText] = useState(true);
  const [secureConfirmText, setSecureConfirmText] = useState(true);

  /**
   * Manejador del botón Registrarse
   */
  const handleRegister = async () => {
    // 1. Validación de campos vacíos
    if (!fullName || !email || !password || !confirmPassword) {
      Alert.alert('Campos vacíos', 'Por favor, completa todos los campos.');
      return;
    }

    // 2. Validación de coincidencia de contraseñas
    if (password !== confirmPassword) {
      Alert.alert('Error', 'Las contraseñas ingresadas no coinciden.');
      return;
    }

    // 3. Intento de registro en Firebase
    try {
      await registerWithEmail(email, password);
      
      Alert.alert(
        '¡Registro Exitoso!',
        'Cuenta creada correctamente. Te enviamos un correo de verificación. Por favor, revisá tu bandeja de entrada.',
        [
          { text: 'Ir al Login', onPress: () => router.replace('/') } // Te manda directo al Login
        ]
      );
    } catch (error) {
      // Muestra errores traducidos (ej: "Este correo ya está registrado por otra cuenta.")
      Alert.alert('Error de Registro', error.message);
    }
  };

  return (
    <View style={loginStyles.container}>
      <Image source={require('../assets/images/MapaVacio2.png')}style={loginStyles.MapaFondo2}/>
      {/* 35% Superior: Dejamos el espacio libre para el mapa de fondo en el futuro */}
      <View style={loginStyles.headerContainer} />


      {/* Formulario contenedor (Reutiliza tu tarjeta blanca/celeste redondeada) */}
      <View style={loginStyles.formContainer2}>
        
        {/* 🦉 El logo del búho posicionado arriba del título como marcaste en el Figma */}
        <Image 
          source={require('../assets/images/Logo-mappealo.png')} 
          style={[loginStyles.logo, { alignSelf: 'center', marginBottom: 15, width: 90, height: 90 }]} 
        />

        <Text style={loginStyles.title}>Crear una cuenta</Text>
        <Text style={[loginStyles.subtitle, { marginBottom: 20 }]}>Regístrate para empezar a usar Mappealo</Text>

        {/* Input: Nombre Completo */}
        <CustomInput 
          placeholder="Nombre completo" 
          value={fullName} 
          onChangeText={setFullName} 
        />

        {/* Input: Correo Electrónico */}
        <CustomInput 
          placeholder="Correo electrónico" 
          value={email} 
          onChangeText={setEmail} 
        />

        {/* Input: Contraseña */}
        <CustomInput 
          placeholder="Contraseña" 
          value={password} 
          onChangeText={setPassword} 
          isPassword={true}
          secureText={secureText}
          onToggleSecure={() => setSecureText(!secureText)}
        />

        {/* Input: Confirmar Contraseña */}
        <CustomInput 
          placeholder="Confirmar contraseña" 
          value={confirmPassword} 
          onChangeText={setConfirmPassword} 
          isPassword={true}
          secureText={secureConfirmText}
          onToggleSecure={() => setSecureConfirmText(!secureConfirmText)}
        />

        {/* Botón Registrarse (Usa tu CustomButton) */}
        <CustomButton title="Registrarse" onPress={handleRegister} />

        {/* Footer: Enlace para volver si ya tiene cuenta */}
        <View style={loginStyles.footer}>
          <Text style={loginStyles.footerText}>¿Ya tienes cuenta? </Text>
          <TouchableOpacity onPress={() => router.back()}>
            <Text style={loginStyles.signUpText}>Inicia sesión</Text>
          </TouchableOpacity>
        </View>

      </View>
    </View>
  );
}