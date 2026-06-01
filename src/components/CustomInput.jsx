import { StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { COLORS } from '../constants/colors';

export const CustomInput = ({ placeholder, value, onChangeText, isPassword, secureText, onToggleSecure }) => {
  return (
    //contendor principal del input
    <View style={styles.inputWrapper}>
      <TextInput
        placeholder={placeholder}                  //texto guia del campo 
        placeholderTextColor={COLORS.placeholder}  
        value={value}                              //variable de estado que almacena el texto
        onChangeText={onChangeText}                //función para actualizar el estado al escribir
        secureTextEntry={secureText}               //booleano:define si oculta el texto con puntitos
        autoCapitalize="none"                      // Evita que ponga mayúscula automáticamente
        style={styles.textInput}
      />
        {/*ispassword,booleano:define si el campo maneja contraseñas,
       ontagglesecure es una función de acción al presionar el botón de mostrar/ocultar,*/}


      {/*CONDICIONAL: Si es un campo de contraseña (isPassword === true), dibuja el botón */}
      {isPassword && (     
        <TouchableOpacity onPress={onToggleSecure} style={styles.showButton}>
          {/** OPERADOR TERNARIO: Cambia el texto dinámicamente según el estado de la visibilidad */}
          <Text style={styles.showText}>{secureText ? 'mostrar' : 'ocultar'}</Text>
        </TouchableOpacity>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  inputWrapper: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.inputBackground,
    borderRadius: 25,
    paddingHorizontal: 20,
    height: 55,
    marginBottom: 15,
  },
  textInput: {
    flex: 1,
    color: COLORS.textPrimary,
    fontSize: 16,
  },
  showText: {
    color: COLORS.textSecondary,
    fontWeight: '500',
  },
});