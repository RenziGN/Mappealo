function isValidEmail(email) {

    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if(regex.test(email) && email.length <= 150) {
        return true;
    } else {
        return false;
    }
}

function isValidPassword(password) {

    if(password.length >= 5 && password.length <= 255) {
        return true;
    } else {
        return false;
    }
}
