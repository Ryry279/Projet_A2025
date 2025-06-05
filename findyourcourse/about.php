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
        <p>Find Your Course est une entreprise ambitieuse qui fait son entrée sur le marché des formations e-learning. Sa vision est de proposer un contenu de formations en ligne pour débutants souhaitant une première approche sur les ERP, avec un focus particulier sur Salesforce. L'objectif est d'introduire les sujets théoriques gratuitement pour la plupart, et de proposer des vidéos e-learning plus détaillées et pratiques, notamment sur Salesforce, en tant que contenu payant.</p>
        <p>Leur ambition est de lancer une première plateforme d'apprentissage 100% digitalisée.</p>
    </section>

    <section class="consulting-firm reveal-on-scroll card-style-section" style="background-color: #fff; padding: 30px; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom:30px;">
        <h2>Notre Cabinet de Conseil : EME</h2>
        <p>Félicitations, vous venez de créer officiellement votre cabinet de conseil: [votre nom], avec une mission et des valeurs claires. C'est en tant que consultants que nous intervenons pour Find Your Course afin de mener à bien le lancement de leur plateforme e-learning. Notre mission est de concevoir, développer et lancer cette plateforme, en apportant notre expertise technique et notre vision stratégique.</p>
        <p>Ce site vitrine lui-même est une démonstration de notre capacité à concrétiser les ambitions de nos clients, présentant le concept e-learning, l'entreprise Find Your Course, et notre cabinet de conseil.</p>
        <h3>Nos Valeurs (Exemple)</h3>
        <ul>
            <li><strong>Innovation :</strong> Apporter des solutions modernes et efficaces.</li>
            <li><strong>Qualité :</strong> Livrer des produits robustes et une expérience utilisateur soignée.</li>
            <li><strong>Partenariat :</strong> Travailler en étroite collaboration avec nos clients pour atteindre leurs objectifs.</li>
        </ul>
    </section>

    <section class="team-presentation reveal-on-scroll">
        <h2>Notre Équipe de Consultants</h2>
        <p>Chaque membre de notre équipe de 3 personnes (dont 1 chef de projet) travaille sur l'ensemble des domaines abordés et sera en capacité de justifier les choix, les outils et méthodes utilisés.</p>
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

<?php
require_once 'includes/footer.php';
?>