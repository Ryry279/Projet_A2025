<?php
// about.php
$page_title = "À Propos de Find Your Course";
$page_description = "Découvrez la mission de Find Your Course et l'équipe de consultants derrière cette plateforme e-learning innovante.";
require_once 'includes/header.php'; // Contains functions.php and db_connect.php

// You can fetch team member details from a database if you expand on this
// For now, static example based on project document hints
$team_members = [
    [
        'name' => 'Eddy Chef de Projet Consultant',
        'role' => 'Gestion de projet, Expert Salesforce',
        'image' => $baseUrl . '/assets/images/member0.jpg', 
        'bio' => 'Passionné(e) par la transformation digitale et expert(e) en solutions CRM, menant ce projet avec vision et rigueur.'
    ],
    [
        'name' => 'Elyas Consultant Technique',
        'role' => 'Développement Web, Spécialiste Contenu ERP',
        'image' => $baseUrl . '/assets/images/member2.jpg',
        'bio' => 'Avec une solide expertise en développement et une connaissance approfondie des ERP, transforme les concepts en réalité.'
    ],
    [
        'name' => 'Marwane Consultant Marketing & UX',
        'role' => 'Design UX/UI, Stratégie SEO & Contenu',
        'image' => $baseUrl . '/assets/images/member3_placeholder.jpg',
        'bio' => 'Dédié(e) à créer une expérience utilisateur intuitive et à optimiser la visibilité de la plateforme pour atteindre notre public.'
    ]
];
?>

<div class="container">
    <section class="hero-section reveal-on-scroll" style="background-color: transparent; padding-top:0; border-bottom:none;">
        <h1>À Propos de Nous</h1>
        <p class="lead">Découvrez notre mission, nos valeurs et l'équipe qui donne vie à Find Your Course.</p>
    </section>

    <section class="company-overview reveal-on-scroll card-style-section" style="background-color: #fff; padding: 30px; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom:30px;">
        <h2>Notre Client : Find Your Course</h2>
        <p>Find Your Course est une entreprise ambitieuse qui fait son entrée sur le marché des formations e-learning. Sa vision est de proposer un contenu de formations en ligne pour débutants souhaitant une première approche sur les ERP, avec un focus particulier sur Salesforce.</p>
    </section>

    <section class="consulting-firm reveal-on-scroll card-style-section" style="background-color: #fff; padding: 30px; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom:30px;">
        <h2>Notre Cabinet de Conseil : EME Conseil</h2>
        <p>Nous sommes EME Conseil, un cabinet de consultants étudiants composé d'Eddy, Marwane et Elyas. Nous intervenons pour "Find Your Course" afin de mener à bien le lancement de leur plateforme. Notre mission est de concevoir, développer et lancer cette plateforme, en apportant notre expertise technique et notre vision stratégique.</p>
    </section>

    <section class="project-simulator reveal-on-scroll" style="background-color: #f9f9f9; padding: 30px; border-radius: 15px; margin: 40px 0;">
        <h2 style="text-align: center; margin-bottom: 20px;">Estimez le Coût de Votre Projet avec EME Conseil</h2>
        <p style="text-align: center; max-width: 600px; margin: 0 auto 30px auto;">Utilisez notre simulateur pour obtenir une estimation budgétaire rapide pour votre projet digital, basée sur sa complexité et sa durée.</p>
        
        <div id="simulator-app" style="max-width: 700px; margin: auto;">
            <div class="form-group">
                <label style="font-weight: bold; font-size: 1.1em;">1. Choisissez le niveau de complexité :</label>
                <div class="complexity-options" style="display: flex; justify-content: space-between; margin-top: 10px; gap: 15px; flex-wrap:wrap;">
                    <div class="option">
                        <input type="radio" id="simple" name="complexity" value="1.0" checked>
                        <label for="simple">Simple</label>
                        <small style="display:block; color:#555;">Site vitrine, Landing Page</small>
                    </div>
                    <div class="option">
                        <input type="radio" id="moyen" name="complexity" value="1.5">
                        <label for="moyen">Moyen</label>
                        <small style="display:block; color:#555;">Site avec BD, Espace Membre</small>
                    </div>
                    <div class="option">
                        <input type="radio" id="complexe" name="complexity" value="2.0">
                        <label for="complexe">Complexe</label>
                        <small style="display:block; color:#555;">E-commerce, API, Temps-réel</small>
                    </div>
                </div>
            </div>

            <div class="form-group" style="margin-top: 30px;">
                <label for="duration" style="font-weight: bold; font-size: 1.1em;">2. Définissez la durée estimée du projet : <strong id="duration-output" style="color: #0071e3;">30 jours</strong></label>
                <input type="range" id="duration" name="duration" min="5" max="90" value="30" step="1" style="width: 100%; margin-top: 10px;">
            </div>

            <div class="result" style="margin-top: 40px; text-align: center; background-color: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <h3 style="margin: 0; font-size: 1.2em; color: #555;">Coût Estimé du Projet :</h3>
                <p id="cost-output" style="font-size: 2.5em; font-weight: bold; color: #0071e3; margin: 10px 0 0 0;">
                    </p>
                <small style="display:block; color:#888; margin-top:10px;">Cette estimation est fournie à titre indicatif et ne constitue pas un devis formel.</small>
            </div>
        </div>
    </section>
    <section class="team-presentation reveal-on-scroll">
        <h2 style="text-align:center;">Notre Équipe de Consultants EME</h2>
        <div class="team-grid" style="margin-top: 20px;">
            <?php foreach ($team_members as $member): ?>
            <div class="team-member">
                <img src="<?php echo htmlspecialchars($member['image']); ?>" alt="Photo de <?php echo htmlspecialchars($member['name']); ?>">
                <h4><?php echo htmlspecialchars($member['name']); ?></h4>
                <p style="font-weight:500; color: #0071e3;"><?php echo htmlspecialchars($member['role']); ?></p>
                <p style="font-size: 0.85em;"><?php echo htmlspecialchars($member['bio']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<style>
    .project-simulator .complexity-options {
        display: flex;
        gap: 20px;
    }
    .project-simulator .option {
        flex: 1;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        text-align: center;
        cursor: pointer;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .project-simulator .option:has(input:checked) {
        border-color: #0071e3;
        box-shadow: 0 0 0 2px rgba(0, 113, 227, 0.2);
        background-color: #f0f8ff;
    }
    .project-simulator .option input[type="radio"] {
        margin-right: 8px;
    }
    .project-simulator input[type="range"] {
        -webkit-appearance: none;
        appearance: none;
        width: 100%;
        height: 8px;
        background: #ddd;
        outline: none;
        opacity: 0.7;
        transition: opacity .2s;
        border-radius: 5px;
    }
    .project-simulator input[type="range"]:hover {
        opacity: 1;
    }
    .project-simulator input[type="range"]::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 20px;
        height: 20px;
        background: #0071e3;
        cursor: pointer;
        border-radius: 50%;
    }
    .project-simulator input[type="range"]::-moz-range-thumb {
        width: 20px;
        height: 20px;
        background: #0071e3;
        cursor: pointer;
        border-radius: 50%;
        border: none;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Sélection des éléments du DOM
    const simulatorApp = document.getElementById('simulator-app');
    if (simulatorApp) {
        const complexityInputs = document.querySelectorAll('input[name="complexity"]');
        const durationInput = document.getElementById('duration');
        const durationOutput = document.getElementById('duration-output');
        const costOutput = document.getElementById('cost-output');

        // Constantes pour le calcul
        const TEAM_DAILY_RATE = 500; // TJM de base fictif pour l'équipe EME

        // Fonction pour calculer et mettre à jour le coût
        function calculateCost() {
            // Récupérer les valeurs actuelles
            const complexityMultiplier = parseFloat(document.querySelector('input[name="complexity"]:checked').value);
            const durationInDays = parseInt(durationInput.value, 10);
            
            // Calculer le coût
            const estimatedCost = TEAM_DAILY_RATE * complexityMultiplier * durationInDays;
            
            // Mettre à jour l'affichage
            durationOutput.textContent = `${durationInDays} jours`;
            costOutput.textContent = `${estimatedCost.toLocaleString('fr-FR')} €`; // Formatage pour la France
        }

        // Ajouter les écouteurs d'événements
        complexityInputs.forEach(input => input.addEventListener('change', calculateCost));
        durationInput.addEventListener('input', calculateCost); // 'input' pour une mise à jour en temps réel

        // Calcul initial au chargement de la page
        calculateCost();
    }
});
</script>

<?php
require_once 'includes/footer.php';
?>