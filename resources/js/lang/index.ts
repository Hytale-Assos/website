export type Locale = 'fr' | 'en';

export type Messages = Record<string, string>;

export const messages: Record<Locale, Messages> = {
    fr: {
        'meta.title': 'Association Hytale',
        'meta.description':
            'Association étudiante dédiée à Hytale : serveurs vanilla et moddés, cours et initiation au modding en Java.',

        'nav.about': "L'association",
        'nav.offer': 'Ce qu’on propose',
        'nav.event': 'Événement',
        'nav.join': 'Rejoindre',
        'nav.login': 'Connexion',
        'nav.register': 'Inscription',
        'nav.dashboard': 'Tableau de bord',
        'nav.discord': 'Discord',

        'hero.badge': 'Association étudiante · ESGI',
        'hero.title': 'Crée, joue et modde Hytale avec nous.',
        'hero.subtitle':
            'Hytale Assos rassemble les passionnés du jeu au sein de l’école. Nous faisons tourner des serveurs vanilla et moddés, et nous t’apprenons à coder tes propres mods en Java — de zéro à ton premier mod.',
        'hero.ctaPrimary': 'Créer mon compte',
        'hero.ctaSecondary': 'Découvrir nos serveurs',
        'hero.discordHint': 'Déjà membre ? Viens échanger sur',
        'hero.stat1': 'Membres actifs',
        'hero.stat2': 'Serveurs officiels',
        'hero.stat3': 'Ateliers par an',
        'hero.stat4': 'Projets maison',

        'about.title': 'Une asso, une passion : Hytale',
        'about.subtitle':
            'Hytale est un jeu d’aventure et de création. Nous en faisons un terrain de jeu collectif au sein de l’ESGI.',
        'about.p1':
            'Notre association réunit les étudiants curieux de Hytale, que tu sois un joueur chevronné ou que tu découvres le jeu. Ensemble, on explore l’aventure, on construit, on partage et on apprend.',
        'about.p2':
            'Notre spécialité, c’est le modding : nous concevons nos propres mods, nous les testons sur nos serveurs et nous formons les nouveaux à la programmation Java. Une vraie porte d’entrée vers le développement de jeu vidéo.',

        'offer.title': 'Ce qu’on propose',
        'offer.subtitle':
            'Des serveurs pour jouer, des ateliers pour apprendre, des événements pour se retrouver.',
        'offer.vanilla.title': 'Serveurs vanilla',
        'offer.vanilla.desc':
            'Survie, construction et événements sur nos serveurs officiels. Un espace sain et encadré pour jouer entre étudiants.',
        'offer.modded.title': 'Serveurs moddés',
        'offer.modded.desc':
            'Découvre les mods maison de l’asso, teste les nouveautés en avant-première et propose tes propres idées.',
        'offer.java.title': 'Initiation au modding Java',
        'offer.java.desc':
            'Des cours et ateliers pour apprendre à coder des mods Hytale en Java, pas à pas, même en partant de zéro.',
        'offer.events.title': 'Événements & tournois',
        'offer.events.desc':
            'Soirées jeux, défis créatifs et rencontres au sein de l’ESGI pour progresser et rigoler ensemble.',

        'event.tag': 'Prochain événement',
        'event.title': 'Atelier : ton premier mod Hytale',
        'event.date': 'Date à venir',
        'event.location': 'Sur le campus ESGI',
        'event.desc':
            'Une session d’initiation pour installer ton environnement, comprendre la structure d’un mod et écrire ton premier bloc de code en Java. Aucun prérequis.',
        'event.cta': 'S’inscrire à l’atelier',
        'event.footer': 'Places limitées · Ouvert aux débutants',

        'join.title': 'Comment rejoindre',
        'join.subtitle':
            'Crée ton compte sur le site, puis viens discuter avec nous sur le Discord.',
        'join.step1.title': 'Crée ton compte',
        'join.step1.desc':
            'Inscris-toi directement sur le site avec ton email pour rejoindre l’association.',
        'join.step2.title': 'Active ton profil',
        'join.step2.desc':
            'Complète ton profil de membre : pseudo, centres d’intérêt, niveau en modding.',
        'join.step3.title': 'Rejoins le Discord',
        'join.step3.desc':
            'Discute, échange et organise tes sessions de jeu et ateliers avec les membres.',
        'join.cta': 'Créer mon compte',
        'join.ctaAlt': 'Rejoindre le Discord',
        'footer.tagline':
            'Association étudiante autour du jeu Hytale à l’ESGI.',
        'footer.rights': 'Tous droits réservés.',
        'footer.builtWith': 'Fait avec passion par les membres.',
        'footer.discord': 'Discord',

    },
    en: {
        'meta.title': 'Hytale Club',
        'meta.description':
            'Student club for Hytale: vanilla and modded servers, plus Java modding lessons for beginners.',

        'nav.about': 'The club',
        'nav.offer': 'What we offer',
        'nav.event': 'Event',
        'nav.join': 'Join us',
        'nav.login': 'Log in',
        'nav.register': 'Register',
        'nav.dashboard': 'Dashboard',
        'nav.discord': 'Discord',

        'hero.badge': 'Student club · ESGI',
        'hero.title': 'Build, play and mod Hytale with us.',
        'hero.subtitle':
            'Hytale Club brings together students who love the game. We run vanilla and modded servers, and we teach you how to code your own mods in Java — from scratch to your first mod.',
        'hero.ctaPrimary': 'Create my account',
        'hero.ctaSecondary': 'Explore our servers',
        'hero.discordHint': 'Already a member? Come chat on',
        'hero.stat1': 'Active members',
        'hero.stat2': 'Official servers',
        'hero.stat3': 'Workshops a year',
        'hero.stat4': 'In-house projects',

        'about.title': 'One club, one passion: Hytale',
        'about.subtitle':
            'Hytale is a game of adventure and creation. We turn it into a shared playground at ESGI.',
        'about.p1':
            'Our club gathers students who are curious about Hytale, whether you are a veteran player or just discovering the game. Together we explore, build, share and learn.',
        'about.p2':
            'Our specialty is modding: we design our own mods, test them on our servers and train newcomers in Java programming. A real gateway into game development.',

        'offer.title': 'What we offer',
        'offer.subtitle':
            'Servers to play on, workshops to learn, events to meet up.',
        'offer.vanilla.title': 'Vanilla servers',
        'offer.vanilla.desc':
            'Survival, building and events on our official servers. A friendly, moderated space to play with fellow students.',
        'offer.modded.title': 'Modded servers',
        'offer.modded.desc':
            'Discover the club’s in-house mods, try new features early and pitch your own ideas.',
        'offer.java.title': 'Java modding 101',
        'offer.java.desc':
            'Lessons and workshops to learn how to code Hytale mods in Java, step by step, even from zero.',
        'offer.events.title': 'Events & tournaments',
        'offer.events.desc':
            'Game nights, creative challenges and meetups at ESGI to level up and have fun together.',

        'event.tag': 'Next event',
        'event.title': 'Workshop: your first Hytale mod',
        'event.date': 'Date coming soon',
        'event.location': 'On the ESGI campus',
        'event.desc':
            'A beginner session to set up your environment, understand a mod’s structure and write your first line of Java code. No prerequisites.',
        'event.cta': 'Sign up for the workshop',
        'event.footer': 'Limited seats · Open to beginners',

        'join.title': 'How to join',
        'join.subtitle':
            'Create your account on the site, then come chat with us on Discord.',
        'join.step1.title': 'Create your account',
        'join.step1.desc':
            'Sign up right here on the site with your email to join the club.',
        'join.step2.title': 'Set up your profile',
        'join.step2.desc':
            'Complete your member profile: nickname, interests, modding level.',
        'join.step3.title': 'Join the Discord',
        'join.step3.desc':
            'Chat, connect and organise your play and workshop sessions with members.',
        'join.cta': 'Create my account',
        'join.ctaAlt': 'Join the Discord',

        'footer.tagline': 'Student club around the game Hytale at ESGI.',
        'footer.rights': 'All rights reserved.',
        'footer.builtWith': 'Made with passion by our members.',
        'footer.discord': 'Discord',

    },
};

export const localeLabels: Record<Locale, string> = {
    fr: 'FR',
    en: 'EN',
};
