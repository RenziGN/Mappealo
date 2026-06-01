// src/styles/loginStyles.js
import { Dimensions, StyleSheet } from 'react-native';
import { COLORS } from '../constants/colors';

const { height } = Dimensions.get('window');

export const loginStyles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#E5E5E5', // Color gris base que simula el fondo del mapa
  },
  headerContainer: {
    height: height * 0.35, // Ocupa el 35% superior para el búho
    justifyContent: 'center',
    alignItems: 'center',
  },
  MapaFondo:{
    position:'absolute',
    width:'100%',
    height:'100%'
  }
,
MapaFondo2:{
  position:'absolute',
  width: '100%',
  height: '300'

},
  logo: {
    width: 130,
    height: 130,
    resizeMode: 'contain',
  },
  formContainer: {
    flex: 1,
    backgroundColor: COLORS.backgroundForm,
    borderTopLeftRadius: 35, 
    borderTopRightRadius: 35,
    paddingHorizontal: 30,
    paddingTop: 40,
  },
  formContainer2: {
  position: 'absolute',      
  top: '25%',                 
  width: '100%',            
  backgroundColor: COLORS.backgroundForm,
  borderRadius: 35,  
  paddingHorizontal: 30,
  paddingTop: 20,
  paddingBottom: 70,  
},
  title: {
    fontSize: 26,
    fontWeight: 'bold',
    color: COLORS.textPrimary,
    textAlign: 'center',
    marginBottom: 8,
  },
  subtitle: {
    fontSize: 15,
    color: COLORS.textSecondary,
    textAlign: 'center',
    marginBottom: 30,
    lineHeight: 22,
  },
  forgotContainer: {
    alignSelf: 'center',
    marginBottom: 25,
  },
  forgotText: {
    color: COLORS.link,
    fontWeight: '600',
    fontSize: 14,
  },
  footer: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    marginTop: 25,
  },
  footerText: {
    color: COLORS.textSecondary,
    fontSize: 14,
  },
  signUpText: {
    color: COLORS.link,
    fontWeight: 'bold',
    fontSize: 14,
  },
});