<?php
// index.php (Homepage)
$page_title = "Accueil - Find Your Course";
$page_description = "Bienvenue sur Find Your Course, votre plateforme e-learning pour débutants souhaitant une première approche sur les ERP avec un focus sur Salesforce.";
// $page_keywords = "e-learning, ERP, Salesforce, formations en ligne, cours débutants"; // Add to header.php if desired

require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Team members (static example for now, could be fetched from DB)
$team_members = [
    [
        'name' => 'Chef de Projet Étudiant(e)',
        'role' => 'Gestion, Vision Stratégique',
        'image' => getBaseUrl() . '/assets/images/member0.jpg',
    
        'bio' => 'Dirige le projet avec un focus sur les objectifs client et la qualité.'
    ],
    [
        'name' => 'Consultant(e) Technique Étudiant(e)',
        'role' => 'Développement, Contenu ERP/Salesforce',
        'image' => getBaseUrl() . '/assets/images/member2.jpg',
        'bio' => 'Assure la robustesse technique et la pertinence du contenu pédagogique.'
    ],
    [
        'name' => 'Consultant(e) UX/Marketing Étudiant(e)',
        'role' => 'Design, SEO, Expérience Utilisateur',
        'image' => getBaseUrl() . '/assets/images/member3_placeholder.jpg',
        'bio' => 'Optimise l\'expérience utilisateur et la visibilité de la plateforme.'
    ]
];
?>

<section class="hero-section reveal-on-scroll">
    <h1>Apprenez les ERP & Salesforce, Simplement.</h1>
    <p class="lead">
        Votre première étape pour maîtriser les technologies d'entreprise. Des formations conçues pour les débutants, claires et pratiques.
    </p>
    <a href="<?php echo getBaseUrl(); ?>/courses.php" class="button" style="font-size: 1.1em; padding: 15px 35px;">Découvrir nos Formations</a>
</section>

<div class="container">
    <section id="about-intro" class="company-overview reveal-on-scroll card-style-section" style="background-color: #fff; padding: 30px; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom:40px; text-align:center;">
        <h2>Bienvenue sur Find Your Course</h2>
        <p>Find Your Course a pour ambition de démystifier les systèmes ERP et la plateforme Salesforce pour les débutants. Nous proposons des contenus accessibles, avec des bases théoriques gratuites et des démonstrations pratiques premium pour approfondir vos compétences.</p>
        <p>Ce projet est réalisé par <strong> EME </strong>, votre partenaire consultant pour le lancement de cette plateforme 100% digitalisée.</p>
        <a href="<?php echo getBaseUrl(); ?>/about.php" class="button button-secondary">En savoir plus sur nous</a>
    </section>

    <section id="featured-courses" class="featured-courses reveal-on-scroll">
        <h2 style="text-align:center; margin-bottom:30px;">Formations à la Une</h2>
        <div class="course-grid">
            <?php
            // Fetch a few featured courses (e.g., 3 random ones or specific IDs)
            $user_id_for_fav_check = isLoggedIn() ? $_SESSION['user_id'] : 0;
            $stmt_featured = $conn->prepare("
                SELECT c.*, cat.name as category_name,
                       (SELECT COUNT(*) FROM favorites f WHERE f.course_id = c.id AND f.user_id = ?) AS is_favorited_by_current_user
                FROM courses c 
                LEFT JOIN categories cat ON c.category_id = cat.id 
                WHERE c.id IN (1, 2, 3) OR c.is_premium = FALSE -- Example criteria for featured
                ORDER BY RAND() 
                LIMIT 3
            "); // Adjust LIMIT and WHERE clause as needed
            $stmt_featured->bind_param("i", $user_id_for_fav_check);
            $stmt_featured->execute();
            $result_featured = $stmt_featured->get_result();
            
            if ($result_featured->num_rows > 0) {
                while ($course_data = $result_featured->fetch_assoc()) {
                    $course_data['is_favorited_by_current_user'] = (bool)$course_data['is_favorited_by_current_user'];
                    $course = $course_data; // Set $course for the template
                    include __DIR__ . '/templates/course_template.php';
                }
            } else {
                echo '<p style="text-align:center;">Aucune formation à la une pour le moment. <a href="' . getBaseUrl() . '/courses.php">Voir toutes les formations</a>.</p>';
            }
            $stmt_featured->close();
            ?>
        </div>
        <div style="text-align:center; margin-top:30px;">
             <a href="<?php echo getBaseUrl(); ?>/courses.php" class="button">Voir Toutes les Formations</a>
        </div>
    </section>

    <hr style="margin: 50px 0; border: 0; border-top: 1px solid #e0e0e0;">

    <section id="team-intro" class="team-presentation reveal-on-scroll">
        <h2 style="text-align:center; margin-bottom:30px;">Notre Équipe de Consultants Étudiants</h2>
        <p style="text-align:center; max-width:700px; margin:0 auto 30px auto;">Nous sommes une équipe de 3 étudiants passionnés, dédiés à la réussite de ce projet annuel pour ESGI 2MCSI (2024-2025).</p>
        <div class="team-grid">
            <?php foreach ($team_members as $member): ?>
            <div class="team-member">
                <img src="<?php echo htmlspecialchars($member['image']); ?>" alt="Photo de <?php echo htmlspecialchars($member['name']); ?>">
                <h4><?php echo htmlspecialchars($member['name']); ?></h4>
                <p style="font-weight:500; color: #0071e3;"><?php echo htmlspecialchars($member['role']); ?></p>
                <p style="font-size: 0.85em;"><?php echo htmlspecialchars($member['bio']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
         <p style="text-align:center; margin-top:20px;"><a href="<?php echo getBaseUrl(); ?>/about.php#team-presentation" class="button button-secondary">Plus sur l'équipe</a></p>
    </section>
</div>

<?php
if (isset($conn)) {
    $conn->close();
}
require_once 'includes/footer.php';
?>