// src/services/AuthService.js
import {
  createUserWithEmailAndPassword,
  sendEmailVerification,
  sendPasswordResetEmail,
  signInWithEmailAndPassword
} from 'firebase/auth';
import { auth } from './FirebaseConfig';

/**
 * Iniciar sesión por correo electrónico
 */
export const loginWithEmail = async (email, password) => {
  try {
    const userCredential = await signInWithEmailAndPassword(auth, email, password);
    const user = userCredential.user;

    if (!user.emailVerified) {
      await sendEmailVerification(user); 
      throw new Error('Por favor, verifica tu correo electrónico. Te enviamos un link de validación.');
    }

    return user; 
    
  } catch (error) {
    if (error.code === 'auth/wrong-password' || error.code === 'auth/user-not-found' || error.code === 'auth/invalid-credential') {
      throw new Error('Correo o contraseña incorrectos.');
    } else if (error.code === 'auth/invalid-email') {
      throw new Error('El formato del correo no es válido.');
    }
    throw new Error(error.message);
  }
};

/**
 * Recuperar contraseña mediante enlace de correo
 */
export const resetPassword = async (email) => {
  try {
    await sendPasswordResetEmail(auth, email);
    return '¡Correo de recuperación enviado con éxito! Revisá tu bandeja de entrada.';
  } catch (error) {
    if (error.code === 'auth/user-not-found') {
      throw new Error('No existe ningún usuario registrado con ese correo.');
    }
    throw new Error('Error al intentar enviar el correo de recuperación.');
  }
};

/**
 * 🔥 NUEVA FUNCIÓN - TAREA: Registrar un nuevo usuario en el sistema
 * @param {string} email - Correo para la nueva cuenta
 * @param {string} password - Contraseña para la nueva cuenta
 */
export const registerWithEmail = async (email, password) => {
  try {
    // Crea el usuario en los servidores de Firebase
    const userCredential = await createUserWithEmailAndPassword(auth, email, password);
    const user = userCredential.user;

    // Le dispara el correo de verificación al instante
    await sendEmailVerification(user);
    
    return user;
  } catch (error) {
    // Traducimos los errores de registro para el usuario
    if (error.code === 'auth/email-already-in-use') {
      throw new Error('Este correo ya está registrado por otra cuenta.');
    } else if (error.code === 'auth/weak-password') {
      throw new Error('La contraseña es muy débil. Debe tener al menos 6 caracteres.');
    } else if (error.code === 'auth/invalid-email') {
      throw new Error('El formato del correo electrónico no es válido.');
    }
    throw new Error(error.message);
  }
};