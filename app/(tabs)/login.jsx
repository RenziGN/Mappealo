import { useState } from 'react';
import { Alert, Image, Text, TouchableOpacity, View } from 'react-native';
import { CustomButton } from '../../src/components/CustomButton';
import { CustomInput } from '../../src/components/CustomInput';
import { loginStyles } from '../../src/styles/LoginStyles';
// Importación de los servicios lógicos que interactúan directamente con Firebase
import { useRouter } from 'expo-router';
import { loginWithEmail, resetPassword } from '../../src/services/AuthService';

export default function LoginScreen() {
  // ESTADOS (Hooks de React): Memoria temporal de la pantalla para capturar el texto del usuario
  const [email, setEmail] = useState('');           // Guarda el correo tipeado
  const [password, setPassword] = useState('');     // Guarda la contraseña tipeada
  const [secureText, setSecureText] = useState(true); // Controla si la clave se oculta (true) o se muestra (false)
  const router = useRouter();
  /**
   * LÓGICA DE INICIO DE SESIÓN
   * Se define como función asíncrona ('async') porque debe esperar la respuesta del servidor de Firebase.
   */
  const handleLogin = async () => {
    // 1. VALIDACIÓN: Evita enviar datos vacíos a la API para ahorrar recursos y prevenir fallos
    if (!email || !password) {
      Alert.alert('Campos vacíos', 'Por favor, completa todos los campos.');
      return; // Corta la ejecución de la función aquí
    }

    // 2. FLUJO ASÍNCRONO: Intentamos conectar con los servidores de Google
    try {
      console.log('Intentando Login con:', email);
      
      // 'await' pausa la ejecución de forma segura hasta que Firebase responda 'Ok' o tire un error
      const user = await loginWithEmail(email, password);
      
      // Si la línea de arriba fue exitosa, se ejecuta esta alerta en el dispositivo
      Alert.alert('¡Bienvenido!', `Sesión iniciada con éxito.`);
      
    } catch (error) {
      // BLOQUE CATCH: Si Firebase rechaza la petición, atrapa el error y muestra el mensaje traducido
      Alert.alert('Error de Inicio de Sesión', error.message);
    }
  };

  /**
   * LÓGICA DE RECUPERACIÓN DE CONTRASEÑA
   */
  const handleForgotPassword = async () => {
    // 1. VALIDACIÓN: Requiere obligatoriamente el email para saber a quién enviarle el enlace
    if (!email) {
      Alert.alert('Atención', 'Por favor, escribe tu correo en el campo de Email para poder enviarte el link.');
      return;
    }

    try {
      console.log('Solicitando recuperación para:', email);
      
      // Llama al servicio de recuperación y espera el mensaje de éxito de Firebase
      const mensajeExito = await resetPassword(email);
      Alert.alert('Recuperar Contraseña', mensajeExito);
    } catch (error) {
      // Atrapa errores (ej: si el correo electrónico ingresado no existe en la base de datos)
      Alert.alert('Error', error.message);
    }
  };

  // INTERFAZ GRÁFICA: Lo que el dispositivo va a renderizar en pantalla
  return (
    <View style={loginStyles.container} 
    >
      
      
      {/* Encabezado: Espacio superior reservado para la identidad visual de la app */}
      <View style={loginStyles.headerContainer}>
        <Image source={require('../assets/images/MapaVacio2.png')} style={loginStyles.MapaFondo}
       resizeMode='cover'/>
        <Image source={require('../assets/images/Logo-mappealo.png')} style={loginStyles.logo} />
      </View>

      {/* Formulario contenedor principal (Tarjeta blanca redondeada) */}
      <View style={loginStyles.formContainer}>
        <Text style={loginStyles.title}>BIENVENIDOS A MAPPEALO!</Text>
        
        {/* Campo de Email: Reutiliza el componente,enviándole sus props correspondientes */}
        <CustomInput 
          placeholder="Email" 
          value={email} 
          onChangeText={setEmail} // Actualiza dinámicamente el estado 'email' al escribir
        />
        
        {/* Campo de Password: Activa propiedades extra para enmascarar los caracteres */}
        <CustomInput 
          placeholder="Contraseña" 
          value={password} 
          onChangeText={setPassword} 
          isPassword={true}           // Le avisa al componente que dibuje el botón 'mostrar/ocultar'
          secureText={secureText}     // Pasa el estado booleano para ocultar/mostrar texto
          onToggleSecure={() => setSecureText(!secureText)} // Invierte el valor al hacer clic (true <-> false)
        />

        {/* Enlace clickable de Olvido de contraseña */}
        <TouchableOpacity onPress={handleForgotPassword} style={loginStyles.forgotContainer}>
          <Text style={loginStyles.forgotText}>Olvidaste tu contraseña?</Text>
        </TouchableOpacity>

        {/* Botón de acción principal: Ejecuta la lógica asíncrona de autenticación */}
        <CustomButton title="INICIAR SESIÓN" onPress={handleLogin} />

        {/* Footer de navegación secundaria (Redirección al registro) */}
        <View style={loginStyles.footer}>
          <Text style={loginStyles.footerText}>No tienes una cuenta? </Text>
          <TouchableOpacity onPress={() => router.push('/register')}>
            <Text style={loginStyles.signUpText}>Registrate</Text>
          </TouchableOpacity>
        </View>

      </View>
    </View>
  );
}