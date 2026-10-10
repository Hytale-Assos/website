export type Locale = 'fr' | 'en';

export type Messages = Record<string, string>;

export const messages: Record<Locale, Messages> = {
    fr: {
        'meta.title': 'Association Hytale',
        'meta.description':
            'Association étudiante dédiée à Hytale : serveurs vanilla et moddés, cours et initiation au modding en Java.',

        'dash.title': 'Tableau de bord',
        'dash.description':
            'Retrouve tes serveurs, ta whitelist et tes sessions Hytale.',
        'dash.tabs.points': 'Points Open',
        'dash.tabs.servers': 'Serveurs',
        'dash.tabs.sessions': 'Mes sessions',
        'dash.tabs.account': 'Mon compte',
        'dash.error.title': 'API Hytale injoignable',
        'dash.error.retry': 'Réessayer',
        'dash.refresh': 'Rafraîchir',

        'dash.linkPrompt.title': 'Liez vos comptes',
        'dash.linkPrompt.hytaleMissing':
            'Ton compte Hytale n’est pas encore renseigné.',
        'dash.linkPrompt.discordMissing':
            'Ton compte Discord n’est pas encore lié.',
        'dash.linkPrompt.description':
            'Lier tes comptes te donne accès aux serveurs, à la whitelist et au suivi de tes points.',
        'dash.linkPrompt.yes': 'Oui',
        'dash.linkPrompt.later': 'Plus tard',

        'dash.account.title': 'Compte Hytale',
        'dash.account.connected': 'Compte lié',
        'dash.account.notConnected': 'Aucun compte Hytale lié',
        'dash.account.hytaleId': 'Identifiant Hytale',
        'dash.account.oauthNote':
            'La connexion « Se connecter avec Hytale » (OAuth2) arrive bientôt. Une fois ton compte lié, tu pourras rejoindre les serveurs en un clic.',
        'dash.account.soon': 'Bientôt disponible',

        'dash.servers.title': 'Serveurs disponibles',
        'dash.servers.description':
            'Rejoins un serveur pour être ajouté à sa whitelist.',
        'dash.servers.empty': 'Aucun serveur disponible pour le moment.',
        'dash.servers.join': 'Rejoindre',
        'dash.servers.leave': 'Quitter',
        'dash.servers.joined': 'Tu es whitelisté',
        'dash.servers.visit': 'Visiter',
        'dash.servers.count': 'serveurs',
        'dash.servers.address': 'Adresse du serveur',
        'dash.servers.copy': 'Copier l’adresse',
        'dash.servers.copied': 'Adresse copiée',
        'dash.servers.enterInGame':
            'Colle cette adresse dans le jeu pour rejoindre le serveur.',

        'dash.sessions.title': 'Mes sessions de jeu',
        'dash.sessions.description':
            'Ton historique de connexions sur les serveurs.',
        'dash.sessions.empty': 'Aucune session enregistrée.',
        'dash.sessions.open': 'En cours',
        'dash.sessions.closed': 'Terminée',
        'dash.sessions.joined': 'Connexion',
        'dash.sessions.ended': 'Déconnexion',
        'dash.sessions.duration': 'Durée',

        'dash.mock.banner':
            'Mode démo : l’API Hytale n’est pas connectée, les données affichées sont fictives.',

        'dash.noHytaleId.title': 'Compte Hytale non lié',
        'dash.noHytaleId.description':
            'Lie ton compte Hytale pour rejoindre des serveurs et voir tes sessions.',

        'dash.activity.viewAll': 'Tout voir',

        'dash.maps.title': 'Cartes',
        'dash.maps.description':
            'Visualise la carte des serveurs en temps réel.',
        'dash.maps.selectServer': 'Choisir un serveur',
        'dash.maps.serverPlaceholder': 'Sélectionne un serveur',
        'dash.maps.empty': 'La carte arrive bientôt',
        'dash.maps.comingSoon':
            'Rendu de carte en temps réel (type BlueMap / Dynmap) prochainement disponible. Sélectionne un serveur pour préparer l’affichage.',
        'dash.maps.badge': 'En développement',
        'dash.maps.cta': 'Bientôt disponible',
        'dash.maps.noServer': 'Aucun serveur disponible.',
        'dash.maps.openExternal': 'Ouvrir la carte',

        'dash.mods.title': 'Mods',
        'dash.mods.description':
            'Les mods créés par les membres de l’association.',
        'dash.mods.empty': 'Aucun mod pour le moment',
        'dash.mods.comingSoon':
            'Les mods créés par les membres seront listés ici, avec un lien vers leur dépôt Git.',
        'dash.mods.badge': 'En développement',
        'dash.mods.cta': 'Voir le dépôt',
        'dash.mods.repository': 'Dépôt',

        'dash.points.title': 'Points Open',
        'dash.points.description':
            'Gagne des points en jouant sur les serveurs de l’association.',
        'dash.points.balance': 'Solde de points',
        'dash.points.totalHours': 'Heures jouées',
        'dash.points.progress': 'Progression vers le prochain point',
        'dash.points.toNext': 'Encore {hours} h avant +0,5 point',
        'dash.points.ready': 'Seuil atteint, point crédité',
        'dash.points.unlockAt': 'Prochain point disponible après le {date}',
        'dash.points.rule':
            'Règle : 0,5 point dès 2 h de jeu cumulées dans la semaine. Les heures en dessous du seuil sont reportées. Un seul point par semaine.',
        'dash.points.history': 'Détail par semaine',
        'dash.points.week': 'Semaine du {date}',
        'dash.points.weekHours': '{hours} h jouées',
        'dash.points.carry': 'Report : {hours} h',
        'dash.points.rewarded': '0,5 pt gagné',
        'dash.points.notRewarded': 'Pas de point',
        'dash.points.empty':
            'Aucune session enregistrée pour le moment. Joue 2 h pour gagner 0,5 point.',
        'dash.points.cap': 'Plafond par le jeu : {points} pts',
        'dash.points.capReached':
            'Plafond atteint : {points} pts gagnés en jouant. D’autres points peuvent être obtenus via d’autres activités.',
        'dash.points.capProgress': '{points} / {max} pts via le jeu',

        'common.save': 'Enregistrer',
        'common.cancel': 'Annuler',
        'common.close': 'Fermer',
        'common.back': 'Retour',
        'common.continue': 'Continuer',
        'common.confirm': 'Confirmer',
        'common.remove': 'Retirer',
        'common.removeAria': 'Retirer',
        'common.yes': 'Oui',
        'common.no': 'Non',
        'common.showPassword': 'Afficher le mot de passe',
        'common.hidePassword': 'Masquer le mot de passe',
        'nav.platform': 'Plateforme',

        'userMenu.settings': 'Paramètres',
        'userMenu.language': 'Langue',
        'userMenu.logout': 'Se déconnecter',

        'nav.items.dashboard': 'Tableau de bord',
        'nav.items.servers': 'Serveurs',
        'nav.items.sessions': 'Sessions',
        'nav.items.maps': 'Cartes',
        'nav.items.mods': 'Mods',
        'nav.items.points': 'Points Open',
        'nav.items.repository': 'Dépôt',
        'nav.items.documentation': 'Documentation',
        'nav.menu': 'Menu de navigation',

        'settings.title': 'Paramètres',
        'settings.description':
            'Gérez votre profil et les paramètres de votre compte',
        'settings.nav.profile': 'Profil',
        'settings.nav.identity': 'Identité',
        'settings.nav.security': 'Sécurité',
        'settings.nav.accounts': 'Comptes liés',
        'settings.nav.invitations': 'Invitations',
        'settings.nav.data': 'Données et confidentialité',
        'settings.nav.appearance': 'Apparence',

        'settings.profile.head': 'Réglages du profil',
        'settings.profile.title': 'Profil',
        'settings.profile.description':
            'Mettez à jour votre nom et votre adresse email',
        'settings.profile.name': 'Nom',
        'settings.profile.namePlaceholder': 'Nom complet',
        'settings.profile.email': 'Adresse email',
        'settings.profile.public': 'Apparaître sur le classement public',
        'settings.profile.publicHint':
            'Seul votre pseudo de jeu peut apparaître publiquement. Votre nom, votre adresse email et vos données d’identification ne sont jamais affichés.',
        'settings.profile.unverified':
            'Votre adresse email n’est pas vérifiée.',
        'settings.profile.resend':
            'Cliquez ici pour renvoyer l’email de vérification.',
        'settings.profile.resent':
            'Un nouveau lien de vérification a été envoyé à votre adresse email.',

        'settings.identity.head': 'Réglages d’identité',
        'settings.identity.title': 'Identité',
        'settings.identity.description':
            'Vos données d’identification, privées et chiffrées',
        'settings.identity.internal': 'Membre interne',
        'settings.identity.external': 'Membre externe',
        'settings.identity.statusHint':
            'Votre statut de membre dépend de votre email d’inscription et ne peut pas être modifié ici.',
        'settings.identity.firstname': 'Prénom',
        'settings.identity.firstnamePlaceholder': 'Prénom',
        'settings.identity.lastname': 'Nom',
        'settings.identity.lastnamePlaceholder': 'Nom',
        'settings.identity.schoolEmail': 'Email scolaire',
        'settings.identity.schoolEmailPlaceholder': 'email scolaire',
        'settings.identity.schoolEmailHint':
            'Votre email scolaire fixe votre statut interne ; il ne le modifie pas et ne sert pas à se connecter.',
        'settings.identity.grade': 'Niveau',
        'settings.identity.gradePlaceholder': 'Sélectionnez votre niveau',

        'settings.security.head': 'Réglages de sécurité',
        'settings.security.title': 'Modifier le mot de passe',
        'settings.security.description':
            'Assurez-vous que votre compte utilise un mot de passe long et aléatoire pour rester sûr',
        'settings.security.current': 'Mot de passe actuel',
        'settings.security.new': 'Nouveau mot de passe',
        'settings.security.confirm': 'Confirmer le mot de passe',

        'settings.accounts.head': 'Réglages des comptes liés',
        'settings.accounts.title': 'Comptes liés',
        'settings.accounts.description':
            'Liez vos comptes de jeu à votre profil',
        'settings.accounts.hytale.description':
            'Votre identité Hytale, utilisée pour les serveurs, la liste blanche et les points',
        'settings.accounts.verified': 'Vérifié',
        'settings.accounts.notVerified': 'Non vérifié',
        'settings.accounts.hytale.unverifiedHint':
            'La vérification du compte Hytale n’est pas encore disponible. Saisissez vos informations vous-même : elles seront examinées par l’équipe puis confirmées via Hytale OAuth.',
        'settings.accounts.hytale.verifiedHint':
            'Votre compte Hytale a été confirmé via Hytale OAuth. Ces informations proviennent de Hytale et ne peuvent pas être modifiées ici.',
        'settings.accounts.hytale.nickname': 'Pseudo Hytale',
        'settings.accounts.hytale.nicknamePlaceholder': 'Votre pseudo en jeu',
        'settings.accounts.hytale.id': 'Identifiant Hytale',
        'settings.accounts.hytale.idPlaceholder': 'UUID de votre compte Hytale',
        'settings.accounts.discord.description':
            'Liez votre compte Discord à votre profil',
        'settings.accounts.linked': 'Lié',
        'settings.accounts.notLinked': 'Non lié',
        'settings.accounts.linkedAs': 'Lié en tant que',
        'settings.accounts.discord.hint':
            'Votre identité Discord est fournie par Discord OAuth.',
        'settings.accounts.link': 'Lier',
        'settings.accounts.unlink': 'Délier',

        'settings.invitations.title': 'Invitations',
        'settings.invitations.description':
            'Invitez des personnes externes à créer un compte',
        'settings.invitations.summary':
            '{active} sur {limit} invitations actives. Les adresses email scolaires s’inscrivent seules et ne peuvent pas être invitées. Les invitations en attente expirent après 7 jours.',
        'settings.invitations.email': 'Email de l’invité',
        'settings.invitations.invite': 'Inviter',
        'settings.invitations.invited': 'Invité {date}',
        'settings.invitations.expires': '· expire {date}',
        'settings.invitations.status.accepted': 'Acceptée',
        'settings.invitations.status.pending': 'En attente',
        'settings.invitations.status.revoked': 'Révoquée',
        'settings.invitations.status.expired': 'Expirée',
        'settings.invitations.revoke': 'Révoquer',
        'settings.invitations.empty': 'Vous n’avez encore invité personne.',

        'settings.appearance.head': 'Réglages d’apparence',
        'settings.appearance.title': 'Réglages d’apparence',
        'settings.appearance.description':
            'Mettez à jour les paramètres d’apparence de votre compte',
        'appearance.light': 'Clair',
        'appearance.dark': 'Sombre',
        'appearance.system': 'Système',

        'settings.data.head': 'Données et confidentialité',
        'settings.data.title': 'Données et confidentialité',
        'settings.data.description':
            'Tout ce que le site conserve à votre sujet',
        'settings.data.alert.title':
            'Toutes les données identifiantes sont privées et chiffrées',
        'settings.data.alert.description':
            'Votre nom, votre adresse email et vos données d’identification sont stockés chiffrés. Vous seul pouvez voir cette page. Les données détenues par l’API du cœur du jeu ne sont pas incluses.',
        'settings.data.account': 'Compte',
        'settings.data.account.desc': 'Les détails de votre compte',
        'settings.data.name': 'Nom',
        'settings.data.email': 'Email',
        'settings.data.schoolEmail': 'Email scolaire',
        'settings.data.memberSince': 'Membre depuis',
        'settings.data.identification': 'Identification',
        'settings.data.identification.desc':
            'Vos données d’identification, chiffrées au repos',
        'settings.data.firstname': 'Prénom',
        'settings.data.lastname': 'Nom',
        'settings.data.grade': 'Niveau',
        'settings.data.status': 'Statut',
        'settings.data.public': 'Classement public',
        'settings.data.linked': 'Comptes liés',
        'settings.data.linked.desc': 'Comptes tiers liés à votre profil',
        'settings.data.linkedLabel': 'Lié',
        'settings.data.notLinked': 'Non lié',
        'settings.data.hytaleVerified': 'Compte Hytale vérifié',
        'settings.data.security': 'Sécurité',
        'settings.data.security.desc':
            'Clés d’accès, authentification à deux facteurs et sessions actives',
        'settings.data.twoFactor': 'Authentification à deux facteurs',
        'settings.data.enabled': 'Activée',
        'settings.data.disabled': 'Désactivée',
        'settings.data.enabledSince': 'Activée depuis',
        'settings.data.passkeys': 'Clés d’accès ({count})',
        'settings.data.noPasskeys': 'Aucune clé d’accès enregistrée.',
        'settings.data.passkeyAdded':
            'Ajoutée {date}, dernière utilisation {lastUsed}',
        'settings.data.sessions': 'Sessions actives ({count})',
        'settings.data.noSessions': 'Aucune session active.',
        'settings.data.unknownDevice': 'Appareil inconnu',
        'settings.data.export': 'Exporter mes données (JSON)',

        'deleteUser.title': 'Supprimer le compte',
        'deleteUser.description':
            'Supprimez votre compte et toutes ses ressources',
        'deleteUser.warning': 'Avertissement',
        'deleteUser.warningText':
            'Procédez avec prudence, cette action est irréversible.',
        'deleteUser.confirmTitle':
            'Voulez-vous vraiment supprimer votre compte ?',
        'deleteUser.confirmText':
            'Une fois votre compte supprimé, toutes ses ressources et données seront également définitivement supprimées. Saisissez votre mot de passe pour confirmer que vous souhaitez supprimer définitivement votre compte.',
        'deleteUser.password': 'Mot de passe',

        'twoFactor.title': 'Authentification à deux facteurs',
        'twoFactor.description':
            'Gérez vos paramètres d’authentification à deux facteurs',
        'twoFactor.intro.enable':
            'Lorsque vous activez l’authentification à deux facteurs, un code sécurisé vous sera demandé à la connexion. Ce code se récupère dans une application TOTP sur votre téléphone.',
        'twoFactor.intro.enabled':
            'Un code aléatoire et sécurisé vous sera demandé à la connexion, à récupérer dans l’application TOTP de votre téléphone.',
        'twoFactor.continueSetup': 'Continuer la configuration',
        'twoFactor.enable': 'Activer la 2FA',
        'twoFactor.disable': 'Désactiver la 2FA',
        'twoFactor.recovery.title': 'Codes de récupération 2FA',
        'twoFactor.recovery.description':
            'Les codes de récupération vous permettent de retrouver l’accès si vous perdez votre appareil 2FA. Conservez-les dans un gestionnaire de mots de passe sécurisé.',
        'twoFactor.recovery.hide': 'Masquer',
        'twoFactor.recovery.view': 'Afficher',
        'twoFactor.recovery.viewLabel': '{action} les codes de récupération',
        'twoFactor.recovery.regenerate': 'Régénérer les codes',
        'twoFactor.recovery.hint':
            'Chaque code de récupération est utilisable une seule fois pour accéder à votre compte et est supprimé après usage. Pour en obtenir d’autres, cliquez sur « Régénérer les codes » ci-dessus.',
        'twoFactor.modal.enabledTitle':
            'Authentification à deux facteurs activée',
        'twoFactor.modal.enabledDesc':
            'L’authentification à deux facteurs est activée. Scannez le QR code ou saisissez la clé de configuration dans votre application d’authentification.',
        'twoFactor.modal.verifyTitle': 'Vérifier le code d’authentification',
        'twoFactor.modal.verifyDesc':
            'Saisissez le code à 6 chiffres de votre application d’authentification',
        'twoFactor.modal.enableTitle':
            'Activer l’authentification à deux facteurs',
        'twoFactor.modal.enableDesc':
            'Pour terminer l’activation de l’authentification à deux facteurs, scannez le QR code ou saisissez la clé de configuration dans votre application d’authentification',
        'twoFactor.modal.manual': 'ou saisissez le code manuellement',

        'passkeys.title': 'Clés d’accès',
        'passkeys.description':
            'Gérez vos clés d’accès pour une connexion sans mot de passe',
        'passkeys.empty.title': 'Aucune clé d’accès',
        'passkeys.empty.description':
            'Ajoutez une clé d’accès pour vous connecter sans mot de passe',
        'passkeys.add': 'Ajouter une clé d’accès',
        'passkeys.notSupported':
            'Les clés d’accès ne sont pas prises en charge par ce navigateur.',
        'passkeys.name.label': 'Nom de la clé d’accès',
        'passkeys.name.placeholder': 'ex. MacBook Pro, iPhone',
        'passkeys.name.hint':
            'Un nom vous aide à identifier cette clé d’accès plus tard.',
        'passkeys.register': 'Enregistrer la clé d’accès',
        'passkeys.registering': 'Enregistrement...',
        'passkeys.remove.title': 'Retirer la clé d’accès',
        'passkeys.remove.description':
            'Voulez-vous vraiment retirer la clé d’accès « {name} » ? Vous ne pourrez plus l’utiliser pour vous connecter.',
        'passkeys.remove.submit': 'Retirer la clé d’accès',
        'passkeys.remove.removing': 'Suppression...',
        'passkeys.added': 'Ajoutée {time}',
        'passkeys.lastUsed': 'Dernière utilisation {time}',
        'passkeys.verify.label': 'Se connecter avec une clé d’accès',
        'passkeys.verify.loading': 'Authentification...',
        'passkeys.verify.separator': 'Ou continuer avec un email',

        'auth.login.title': 'Connectez-vous à votre compte',
        'auth.login.description':
            'Saisissez votre email et votre mot de passe pour vous connecter',
        'auth.login.head': 'Connexion',
        'auth.login.email': 'Adresse email',
        'auth.login.password': 'Mot de passe',
        'auth.login.forgot': 'Mot de passe oublié ?',
        'auth.login.remember': 'Se souvenir de moi',
        'auth.login.submit': 'Se connecter',
        'auth.login.noAccount': 'Pas encore de compte ?',
        'auth.login.signUp': 'S’inscrire',
        'auth.register.title': 'Créer un compte',
        'auth.register.description':
            'Renseignez vos informations ci-dessous pour créer votre compte',
        'auth.register.head': 'Inscription',
        'auth.register.notice':
            'L’inscription est ouverte aux membres de l’école avec leur adresse email scolaire. Les invités externes peuvent créer un compte après avoir été invités par un membre interne.',
        'auth.register.name': 'Nom',
        'auth.register.namePlaceholder': 'Nom complet',
        'auth.register.password': 'Mot de passe',
        'auth.register.confirmPassword': 'Confirmer le mot de passe',
        'auth.register.submit': 'Créer le compte',
        'auth.register.haveAccount': 'Déjà un compte ?',
        'auth.register.logIn': 'Se connecter',
        'auth.forgot.title': 'Mot de passe oublié',
        'auth.forgot.description':
            'Saisissez votre email pour recevoir un lien de réinitialisation du mot de passe',
        'auth.forgot.head': 'Mot de passe oublié',
        'auth.forgot.submit': 'Envoyer le lien de réinitialisation',
        'auth.forgot.orReturn': 'Ou retourner à',
        'auth.forgot.logIn': 'la connexion',
        'auth.reset.title': 'Réinitialiser le mot de passe',
        'auth.reset.description': 'Veuillez saisir votre nouveau mot de passe',
        'auth.reset.head': 'Réinitialiser le mot de passe',
        'auth.reset.email': 'Email',
        'auth.reset.submit': 'Réinitialiser le mot de passe',
        'auth.confirm.title': 'Confirmer le mot de passe',
        'auth.confirm.description':
            'Ceci est une zone sécurisée de l’application. Confirmez votre mot de passe avant de continuer.',
        'auth.confirm.head': 'Confirmer le mot de passe',
        'auth.confirm.submit': 'Confirmer le mot de passe',
        'auth.confirm.passkeyLabel': 'Confirmer avec une clé d’accès',
        'auth.confirm.passkeyLoading': 'Confirmation...',
        'auth.confirm.passkeySeparator': 'Ou confirmer avec un mot de passe',
        'auth.verify.title': 'Vérification de l’email',
        'auth.verify.description':
            'Veuillez vérifier votre adresse email en cliquant sur le lien que nous venons de vous envoyer.',
        'auth.verify.head': 'Vérification de l’email',
        'auth.verify.sent':
            'Un nouveau lien de vérification a été envoyé à l’adresse email fournie lors de l’inscription.',
        'auth.verify.resend': 'Renvoyer l’email de vérification',
        'auth.verify.logout': 'Se déconnecter',
        'auth.twoFactor.head': 'Authentification à deux facteurs',
        'auth.twoFactor.recoveryTitle': 'Code de récupération',
        'auth.twoFactor.recoveryDescription':
            'Confirmez l’accès à votre compte en saisissant l’un de vos codes de récupération d’urgence.',
        'auth.twoFactor.recoveryButton':
            'se connecter avec un code d’authentification',
        'auth.twoFactor.codeTitle': 'Code d’authentification',
        'auth.twoFactor.codeDescription':
            'Saisissez le code d’authentification fourni par votre application d’authentification.',
        'auth.twoFactor.codeButton':
            'se connecter avec un code de récupération',
        'auth.twoFactor.recoveryPlaceholder': 'Saisir le code de récupération',
        'auth.twoFactor.orYouCan': 'ou vous pouvez',
    },
    en: {
        'meta.title': 'Hytale Club',
        'meta.description':
            'Student club for Hytale: vanilla and modded servers, plus Java modding lessons for beginners.',

        'dash.title': 'Dashboard',
        'dash.description':
            'Find your servers, your whitelist and your Hytale sessions.',
        'dash.tabs.points': 'Open Points',
        'dash.tabs.servers': 'Servers',
        'dash.tabs.sessions': 'My sessions',
        'dash.tabs.account': 'My account',
        'dash.error.title': 'Hytale API unreachable',
        'dash.error.retry': 'Retry',
        'dash.refresh': 'Refresh',

        'dash.linkPrompt.title': 'Link your accounts',
        'dash.linkPrompt.hytaleMissing':
            'Your Hytale account is not filled in yet.',
        'dash.linkPrompt.discordMissing':
            'Your Discord account is not linked yet.',
        'dash.linkPrompt.description':
            'Linking your accounts gives you access to servers, whitelist and points tracking.',
        'dash.linkPrompt.yes': 'Yes',
        'dash.linkPrompt.later': 'Later',

        'dash.account.title': 'Hytale account',
        'dash.account.connected': 'Account linked',
        'dash.account.notConnected': 'No Hytale account linked',
        'dash.account.hytaleId': 'Hytale identifier',
        'dash.account.oauthNote':
            '“Sign in with Hytale” (OAuth2) is coming soon. Once your account is linked, you will be able to join servers in one click.',
        'dash.account.soon': 'Coming soon',

        'dash.servers.title': 'Available servers',
        'dash.servers.description':
            'Join a server to be added to its whitelist.',
        'dash.servers.empty': 'No server available yet.',
        'dash.servers.join': 'Join',
        'dash.servers.leave': 'Leave',
        'dash.servers.joined': 'You are whitelisted',
        'dash.servers.visit': 'Visit',
        'dash.servers.count': 'servers',
        'dash.servers.address': 'Server address',
        'dash.servers.copy': 'Copy address',
        'dash.servers.copied': 'Address copied',
        'dash.servers.enterInGame':
            'Paste this address in-game to join the server.',

        'dash.sessions.title': 'My gaming sessions',
        'dash.sessions.description': 'Your connection history on servers.',
        'dash.sessions.empty': 'No session recorded.',
        'dash.sessions.open': 'Ongoing',
        'dash.sessions.closed': 'Ended',
        'dash.sessions.joined': 'Joined',
        'dash.sessions.ended': 'Ended',
        'dash.sessions.duration': 'Duration',

        'dash.mock.banner':
            'Demo mode: the Hytale API is not connected, the displayed data is fake.',

        'dash.noHytaleId.title': 'Hytale account not linked',
        'dash.noHytaleId.description':
            'Link your Hytale account to join servers and see your sessions.',

        'dash.activity.viewAll': 'View all',

        'dash.maps.title': 'Maps',
        'dash.maps.description': 'View servers maps in real time.',
        'dash.maps.selectServer': 'Choose a server',
        'dash.maps.serverPlaceholder': 'Select a server',
        'dash.maps.empty': 'The map is coming soon',
        'dash.maps.comingSoon':
            'Real-time map rendering (BlueMap / Dynmap style) available soon. Select a server to prepare the view.',
        'dash.maps.badge': 'In development',
        'dash.maps.cta': 'Coming soon',
        'dash.maps.noServer': 'No server available.',
        'dash.maps.openExternal': 'Open the map',

        'dash.mods.title': 'Mods',
        'dash.mods.description': 'The mods created by club members.',
        'dash.mods.empty': 'No mod yet',
        'dash.mods.comingSoon':
            'Mods created by members will be listed here, with a link to their Git repository.',
        'dash.mods.badge': 'In development',
        'dash.mods.cta': 'View repository',
        'dash.mods.repository': 'Repository',

        'dash.points.title': 'Open Points',
        'dash.points.description':
            'Earn points by playing on the club servers.',
        'dash.points.balance': 'Points balance',
        'dash.points.totalHours': 'Hours played',
        'dash.points.progress': 'Progress to next point',
        'dash.points.toNext': '{hours} h left before +0.5 point',
        'dash.points.ready': 'Threshold reached, point credited',
        'dash.points.unlockAt': 'Next point available after {date}',
        'dash.points.rule':
            'Rule: 0.5 point once 2 h of play accumulate within the week. Hours below the threshold carry over. Only one point per week.',
        'dash.points.history': 'Weekly breakdown',
        'dash.points.week': 'Week of {date}',
        'dash.points.weekHours': '{hours} h played',
        'dash.points.carry': 'Carry-over: {hours} h',
        'dash.points.rewarded': '0.5 pt earned',
        'dash.points.notRewarded': 'No point',
        'dash.points.empty':
            'No session recorded yet. Play 2 h to earn 0.5 point.',
        'dash.points.cap': 'Play cap: {points} pts',
        'dash.points.capReached':
            'Cap reached: {points} pts earned from playing. More points can be earned through other activities.',
        'dash.points.capProgress': '{points} / {max} pts from playing',

        'common.save': 'Save',
        'common.cancel': 'Cancel',
        'common.close': 'Close',
        'common.back': 'Back',
        'common.continue': 'Continue',
        'common.confirm': 'Confirm',
        'common.remove': 'Remove',
        'common.removeAria': 'Remove',
        'common.yes': 'Yes',
        'common.no': 'No',
        'common.showPassword': 'Show password',
        'common.hidePassword': 'Hide password',
        'nav.platform': 'Platform',

        'userMenu.settings': 'Settings',
        'userMenu.language': 'Language',
        'userMenu.logout': 'Log out',

        'nav.items.dashboard': 'Dashboard',
        'nav.items.servers': 'Servers',
        'nav.items.sessions': 'Sessions',
        'nav.items.maps': 'Maps',
        'nav.items.mods': 'Mods',
        'nav.items.points': 'Open Points',
        'nav.items.repository': 'Repository',
        'nav.items.documentation': 'Documentation',
        'nav.menu': 'Navigation menu',

        'settings.title': 'Settings',
        'settings.description': 'Manage your profile and account settings',
        'settings.nav.profile': 'Profile',
        'settings.nav.identity': 'Identity',
        'settings.nav.security': 'Security',
        'settings.nav.accounts': 'Linked accounts',
        'settings.nav.invitations': 'Invitations',
        'settings.nav.data': 'Data & privacy',
        'settings.nav.appearance': 'Appearance',

        'settings.profile.head': 'Profile settings',
        'settings.profile.title': 'Profile',
        'settings.profile.description': 'Update your name and email address',
        'settings.profile.name': 'Name',
        'settings.profile.namePlaceholder': 'Full name',
        'settings.profile.email': 'Email address',
        'settings.profile.public': 'Appear on the public leaderboard',
        'settings.profile.publicHint':
            'Only your game nickname can appear publicly. Your name, email address, and identification data are never shown.',
        'settings.profile.unverified': 'Your email address is unverified.',
        'settings.profile.resend':
            'Click here to re-send the verification email.',
        'settings.profile.resent':
            'A new verification link has been sent to your email address.',

        'settings.identity.head': 'Identity settings',
        'settings.identity.title': 'Identity',
        'settings.identity.description':
            'Your identification data, private and encrypted',
        'settings.identity.internal': 'Internal member',
        'settings.identity.external': 'External member',
        'settings.identity.statusHint':
            'Your member status is set by your registration email and cannot be changed here.',
        'settings.identity.firstname': 'First name',
        'settings.identity.firstnamePlaceholder': 'First name',
        'settings.identity.lastname': 'Last name',
        'settings.identity.lastnamePlaceholder': 'Last name',
        'settings.identity.schoolEmail': 'School email',
        'settings.identity.schoolEmailPlaceholder': 'school email',
        'settings.identity.schoolEmailHint':
            'Your school email anchors your internal status; it does not change it and is not used to sign in.',
        'settings.identity.grade': 'Grade level',
        'settings.identity.gradePlaceholder': 'Select your grade level',

        'settings.security.head': 'Security settings',
        'settings.security.title': 'Update password',
        'settings.security.description':
            'Ensure your account is using a long, random password to stay secure',
        'settings.security.current': 'Current password',
        'settings.security.new': 'New password',
        'settings.security.confirm': 'Confirm password',

        'settings.accounts.head': 'Linked accounts settings',
        'settings.accounts.title': 'Linked accounts',
        'settings.accounts.description':
            'Link your game accounts to your profile',
        'settings.accounts.hytale.description':
            'Your Hytale identity, used for servers, whitelist and points',
        'settings.accounts.verified': 'Verified',
        'settings.accounts.notVerified': 'Not verified',
        'settings.accounts.hytale.unverifiedHint':
            'The Hytale account verification is not available yet. Enter your details yourself: they will be reviewed by the team and confirmed later through Hytale OAuth.',
        'settings.accounts.hytale.verifiedHint':
            'Your Hytale account has been confirmed through Hytale OAuth. These details are provided by Hytale and cannot be changed here.',
        'settings.accounts.hytale.nickname': 'Hytale nickname',
        'settings.accounts.hytale.nicknamePlaceholder': 'Your in-game nickname',
        'settings.accounts.hytale.id': 'Hytale ID',
        'settings.accounts.hytale.idPlaceholder': 'Your Hytale account UUID',
        'settings.accounts.discord.description':
            'Link your Discord account to your profile',
        'settings.accounts.linked': 'Linked',
        'settings.accounts.notLinked': 'Not linked',
        'settings.accounts.linkedAs': 'Linked as',
        'settings.accounts.discord.hint':
            'Your Discord identity is provided by Discord OAuth.',
        'settings.accounts.link': 'Link',
        'settings.accounts.unlink': 'Unlink',

        'settings.invitations.title': 'Invitations',
        'settings.invitations.description':
            'Invite external guests to create an account',
        'settings.invitations.summary':
            '{active} of {limit} active invitations. School email addresses register on their own and cannot be invited. Pending invitations expire after 7 days.',
        'settings.invitations.email': 'Guest email',
        'settings.invitations.invite': 'Invite',
        'settings.invitations.invited': 'Invited {date}',
        'settings.invitations.expires': '· expires {date}',
        'settings.invitations.status.accepted': 'Accepted',
        'settings.invitations.status.pending': 'Pending',
        'settings.invitations.status.revoked': 'Revoked',
        'settings.invitations.status.expired': 'Expired',
        'settings.invitations.revoke': 'Revoke',
        'settings.invitations.empty': 'You have not invited anyone yet.',

        'settings.appearance.head': 'Appearance settings',
        'settings.appearance.title': 'Appearance settings',
        'settings.appearance.description':
            'Update the appearance settings for your account',
        'appearance.light': 'Light',
        'appearance.dark': 'Dark',
        'appearance.system': 'System',

        'settings.data.head': 'Data & privacy',
        'settings.data.title': 'Data & privacy',
        'settings.data.description': 'Everything the website stores about you',
        'settings.data.alert.title':
            'All data that can identify you is private and encrypted',
        'settings.data.alert.description':
            'Your name, email address, and identification details are stored encrypted. Only you can see this page. Data held by the game core API is not included.',
        'settings.data.account': 'Account',
        'settings.data.account.desc': 'Your account details',
        'settings.data.name': 'Name',
        'settings.data.email': 'Email',
        'settings.data.schoolEmail': 'School email',
        'settings.data.memberSince': 'Member since',
        'settings.data.identification': 'Identification',
        'settings.data.identification.desc':
            'Your identification data, encrypted at rest',
        'settings.data.firstname': 'First name',
        'settings.data.lastname': 'Last name',
        'settings.data.grade': 'Grade level',
        'settings.data.status': 'Status',
        'settings.data.public': 'Public leaderboard',
        'settings.data.linked': 'Linked accounts',
        'settings.data.linked.desc':
            'Third-party accounts linked to your profile',
        'settings.data.linkedLabel': 'Linked',
        'settings.data.notLinked': 'Not linked',
        'settings.data.hytaleVerified': 'Hytale account verified',
        'settings.data.security': 'Security',
        'settings.data.security.desc':
            'Passkeys, two-factor authentication and active sessions',
        'settings.data.twoFactor': 'Two-factor authentication',
        'settings.data.enabled': 'Enabled',
        'settings.data.disabled': 'Disabled',
        'settings.data.enabledSince': 'Enabled since',
        'settings.data.passkeys': 'Passkeys ({count})',
        'settings.data.noPasskeys': 'No passkeys registered.',
        'settings.data.passkeyAdded': 'Added {date}, last used {lastUsed}',
        'settings.data.sessions': 'Active sessions ({count})',
        'settings.data.noSessions': 'No active sessions.',
        'settings.data.unknownDevice': 'Unknown device',
        'settings.data.export': 'Export my data (JSON)',

        'deleteUser.title': 'Delete account',
        'deleteUser.description':
            'Delete your account and all of its resources',
        'deleteUser.warning': 'Warning',
        'deleteUser.warningText':
            'Please proceed with caution, this cannot be undone.',
        'deleteUser.confirmTitle':
            'Are you sure you want to delete your account?',
        'deleteUser.confirmText':
            'Once your account is deleted, all of its resources and data will also be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.',
        'deleteUser.password': 'Password',

        'twoFactor.title': 'Two-factor authentication',
        'twoFactor.description':
            'Manage your two-factor authentication settings',
        'twoFactor.intro.enable':
            'When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.',
        'twoFactor.intro.enabled':
            'You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.',
        'twoFactor.continueSetup': 'Continue setup',
        'twoFactor.enable': 'Enable 2FA',
        'twoFactor.disable': 'Disable 2FA',
        'twoFactor.recovery.title': '2FA recovery codes',
        'twoFactor.recovery.description':
            'Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager.',
        'twoFactor.recovery.hide': 'Hide',
        'twoFactor.recovery.view': 'View',
        'twoFactor.recovery.viewLabel': '{action} recovery codes',
        'twoFactor.recovery.regenerate': 'Regenerate codes',
        'twoFactor.recovery.hint':
            'Each recovery code can be used once to access your account and will be removed after use. If you need more, click “Regenerate codes” above.',
        'twoFactor.modal.enabledTitle': 'Two-factor authentication enabled',
        'twoFactor.modal.enabledDesc':
            'Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.',
        'twoFactor.modal.verifyTitle': 'Verify authentication code',
        'twoFactor.modal.verifyDesc':
            'Enter the 6-digit code from your authenticator app',
        'twoFactor.modal.enableTitle': 'Enable two-factor authentication',
        'twoFactor.modal.enableDesc':
            'To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app',
        'twoFactor.modal.manual': 'or, enter the code manually',

        'passkeys.title': 'Passkeys',
        'passkeys.description': 'Manage your passkeys for passwordless sign-in',
        'passkeys.empty.title': 'No passkeys yet',
        'passkeys.empty.description':
            'Add a passkey to sign in without a password',
        'passkeys.add': 'Add passkey',
        'passkeys.notSupported': 'Passkeys are not supported in this browser.',
        'passkeys.name.label': 'Passkey name',
        'passkeys.name.placeholder': 'e.g., MacBook Pro, iPhone',
        'passkeys.name.hint': 'A name helps you identify this passkey later.',
        'passkeys.register': 'Register passkey',
        'passkeys.registering': 'Registering...',
        'passkeys.remove.title': 'Remove passkey',
        'passkeys.remove.description':
            'Are you sure you want to remove the "{name}" passkey? You will no longer be able to use it to sign in.',
        'passkeys.remove.submit': 'Remove passkey',
        'passkeys.remove.removing': 'Removing...',
        'passkeys.added': 'Added {time}',
        'passkeys.lastUsed': 'Last used {time}',
        'passkeys.verify.label': 'Sign in with a passkey',
        'passkeys.verify.loading': 'Authenticating...',
        'passkeys.verify.separator': 'Or continue with email',

        'auth.login.title': 'Log in to your account',
        'auth.login.description':
            'Enter your email and password below to log in',
        'auth.login.head': 'Log in',
        'auth.login.email': 'Email address',
        'auth.login.password': 'Password',
        'auth.login.forgot': 'Forgot your password?',
        'auth.login.remember': 'Remember me',
        'auth.login.submit': 'Log in',
        'auth.login.noAccount': "Don't have an account?",
        'auth.login.signUp': 'Sign up',
        'auth.register.title': 'Create an account',
        'auth.register.description':
            'Enter your details below to create your account',
        'auth.register.head': 'Register',
        'auth.register.notice':
            'Registration is open to school members with their school email address. Outside guests can create an account once an internal member has invited them.',
        'auth.register.name': 'Name',
        'auth.register.namePlaceholder': 'Full name',
        'auth.register.password': 'Password',
        'auth.register.confirmPassword': 'Confirm password',
        'auth.register.submit': 'Create account',
        'auth.register.haveAccount': 'Already have an account?',
        'auth.register.logIn': 'Log in',
        'auth.forgot.title': 'Forgot password',
        'auth.forgot.description':
            'Enter your email to receive a password reset link',
        'auth.forgot.head': 'Forgot password',
        'auth.forgot.submit': 'Email password reset link',
        'auth.forgot.orReturn': 'Or, return to',
        'auth.forgot.logIn': 'log in',
        'auth.reset.title': 'Reset password',
        'auth.reset.description': 'Please enter your new password below',
        'auth.reset.head': 'Reset password',
        'auth.reset.email': 'Email',
        'auth.reset.submit': 'Reset password',
        'auth.confirm.title': 'Confirm password',
        'auth.confirm.description':
            'This is a secure area of the application. Please confirm your password before continuing.',
        'auth.confirm.head': 'Confirm password',
        'auth.confirm.submit': 'Confirm password',
        'auth.confirm.passkeyLabel': 'Confirm with passkey',
        'auth.confirm.passkeyLoading': 'Confirming...',
        'auth.confirm.passkeySeparator': 'Or confirm with password',
        'auth.verify.title': 'Email verification',
        'auth.verify.description':
            'Please verify your email address by clicking on the link we just emailed to you.',
        'auth.verify.head': 'Email verification',
        'auth.verify.sent':
            'A new verification link has been sent to the email address you provided during registration.',
        'auth.verify.resend': 'Resend verification email',
        'auth.verify.logout': 'Log out',
        'auth.twoFactor.head': 'Two-factor authentication',
        'auth.twoFactor.recoveryTitle': 'Recovery code',
        'auth.twoFactor.recoveryDescription':
            'Please confirm access to your account by entering one of your emergency recovery codes.',
        'auth.twoFactor.recoveryButton': 'login using an authentication code',
        'auth.twoFactor.codeTitle': 'Authentication code',
        'auth.twoFactor.codeDescription':
            'Enter the authentication code provided by your authenticator application.',
        'auth.twoFactor.codeButton': 'login using a recovery code',
        'auth.twoFactor.recoveryPlaceholder': 'Enter recovery code',
        'auth.twoFactor.orYouCan': 'or you can',
    },
};

export const localeLabels: Record<Locale, string> = {
    fr: 'FR',
    en: 'EN',
};
