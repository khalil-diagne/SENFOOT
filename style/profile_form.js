/**
 * Gère le formulaire de profil dans le modal du panier
 */

// Récupère le profil de l'utilisateur depuis le serveur
async function fetchUserProfile() {
    try {
        const response = await fetch('get_user_profile.php', {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await response.json();
        if (data.success && data.profile) {
            return data.profile;
        }
    } catch (error) {
        console.error('Erreur lors de la récupération du profil:', error);
    }
    return null;
}

// Affiche le formulaire de profil si des champs sont manquants
async function checkAndShowProfileForm() {
    const profile = await fetchUserProfile();
    const profileFormSection = document.getElementById('profileFormSection');
    
    if (!profileFormSection || !profile) {
        return;
    }

    // Champs requis
    const requiredFields = ['prenom', 'nom', 'email', 'telephone', 'adresse', 'ville'];
    const missingFields = requiredFields.filter(field => !profile[field] || profile[field].trim() === '');

    if (missingFields.length > 0) {
        // Afficher le formulaire
        profileFormSection.style.display = 'block';
        
        // Pré-remplir les champs disponibles
        if (profile.prenom) document.getElementById('formPrenom').value = profile.prenom;
        if (profile.nom) document.getElementById('formNom').value = profile.nom;
        if (profile.email) document.getElementById('formEmail').value = profile.email;
        if (profile.adresse) document.getElementById('formAdresse').value = profile.adresse;
        if (profile.ville) document.getElementById('formVille').value = profile.ville;
        if (profile.telephone) document.getElementById('formTelephone').value = profile.telephone;
    } else {
        // Masquer le formulaire si tous les champs sont remplis
        profileFormSection.style.display = 'none';
    }
}

// Valide le formulaire de profil
function validateProfileForm() {
    const profileFormSection = document.getElementById('profileFormSection');
    
    // Si le formulaire n'est pas visible, pas besoin de valider
    if (profileFormSection.style.display === 'none') {
        return true;
    }

    const prenom = document.getElementById('formPrenom').value.trim();
    const nom = document.getElementById('formNom').value.trim();
    const email = document.getElementById('formEmail').value.trim();
    const adresse = document.getElementById('formAdresse').value.trim();
    const ville = document.getElementById('formVille').value.trim();
    const telephone = document.getElementById('formTelephone').value.trim();

    if (!prenom) {
        alert('Veuillez entrer votre prénom.');
        return false;
    }
    if (!nom) {
        alert('Veuillez entrer votre nom.');
        return false;
    }
    if (!email || !isValidEmail(email)) {
        alert('Veuillez entrer une adresse email valide.');
        return false;
    }
    if (!adresse) {
        alert('Veuillez entrer votre adresse.');
        return false;
    }
    if (!ville) {
        alert('Veuillez entrer votre ville.');
        return false;
    }
    if (!telephone || !/^\d{9}$/.test(telephone)) {
        alert('Veuillez entrer un numéro de téléphone valide (9 chiffres).');
        return false;
    }

    return true;
}

// Valide un email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Récupère les données du formulaire de profil
function getProfileFormData() {
    const profileFormSection = document.getElementById('profileFormSection');
    
    if (profileFormSection.style.display === 'none') {
        return null;
    }

    return {
        prenom: document.getElementById('formPrenom').value.trim(),
        nom: document.getElementById('formNom').value.trim(),
        email: document.getElementById('formEmail').value.trim(),
        adresse: document.getElementById('formAdresse').value.trim(),
        ville: document.getElementById('formVille').value.trim(),
        telephone: document.getElementById('formTelephone').value.trim()
    };
}

// Sauvegarde le profil sur le serveur
async function saveProfileData(profileData) {
    try {
        const response = await fetch('update_profile_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(profileData)
        });
        const data = await response.json();
        return data.success === true;
    } catch (error) {
        console.error('Erreur lors de la sauvegarde du profil:', error);
        return false;
    }
}

// Initialise le formulaire de profil au chargement du panier
async function initProfileForm() {
    await checkAndShowProfileForm();
}

// Appel au chargement de la page
document.addEventListener('DOMContentLoaded', initProfileForm);
