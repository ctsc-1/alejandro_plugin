// Guide d'inscription intelligent
class MembershipGuide {
    constructor() {
        this.questions = {
            business: {
                fr: "Êtes-vous un professionnel ?",
                en: "Are you a professional?",
                es: "¿Es usted un profesional?"
            },
            commerce: {
                fr: "Souhaitez-vous vendre des produits en ligne ?",
                en: "Do you want to sell products online?",
                es: "¿Desea vender productos en línea?"
            },
            showcase: {
                fr: "Souhaitez-vous une page vitrine pour votre activité ?",
                en: "Do you want a showcase page for your business?",
                es: "¿Desea una página escaparate para su negocio?"
            }
        };
    }

    // Détermine le type d'adhésion recommandé
    async determineUserType() {
        const isBusiness = await this.askQuestion('business');
        
        if (!isBusiness) {
            return {
                type: 'premium',
                message: this.getRecommendationMessage('premium')
            };
        }

        const wantsOnlineSales = await this.askQuestion('commerce');
        if (wantsOnlineSales) {
            return {
                type: 'commercant',
                message: this.getRecommendationMessage('commercant')
            };
        }

        const wantsShowcase = await this.askQuestion('showcase');
        if (wantsShowcase) {
            return {
                type: 'annonceur',
                message: this.getRecommendationMessage('annonceur')
            };
        }

        return {
            type: 'premium',
            message: this.getRecommendationMessage('premium')
        };
    }

    // Messages de recommandation personnalisés
    getRecommendationMessage(type) {
        const messages = {
            premium: {
                fr: "L'adhésion Premium est parfaite pour vous ! Profitez de toutes les fonctionnalités premium du Club.",
                en: "Premium membership is perfect for you! Enjoy all Club premium features.",
                es: "¡La membresía Premium es perfecta para usted! Disfrute de todas las características premium del Club."
            },
            annonceur: {
                fr: "Le statut Annonceur Pro est idéal pour votre activité ! Augmentez votre visibilité professionnelle.",
                en: "Pro Advertiser status is ideal for your business! Increase your professional visibility.",
                es: "¡El estado de Anunciante Pro es ideal para su negocio! Aumente su visibilidad profesional."
            },
            commercant: {
                fr: "Le statut Commerçant Elite est fait pour vous ! Gérez votre boutique en ligne et maximisez vos ventes.",
                en: "Elite Merchant status is made for you! Manage your online store and maximize your sales.",
                es: "¡El estado de Comerciante Elite está hecho para usted! Gestione su tienda en línea y maximice sus ventas."
            }
        };

        const lang = document.documentElement.lang || 'fr';
        return messages[type][lang];
    }

    // Affiche une question et retourne la réponse
    async askQuestion(questionType) {
        const lang = document.documentElement.lang || 'fr';
        const question = this.questions[questionType][lang];
        
        return new Promise((resolve) => {
            const modal = this.createModal(question, (result) => {
                modal.remove();
                resolve(result);
            });
            
            document.body.appendChild(modal);
        });
    }

    // Crée une modale pour poser une question
    createModal(question, callback) {
        const modal = document.createElement('div');
        modal.className = 'membership-guide-modal';
        
        const content = `
            <div class="modal-content">
                <h3>${question}</h3>
                <div class="button-group">
                    <button class="btn-yes" onclick="this.parentElement.parentElement.parentElement.__callback(true)">
                        ${document.documentElement.lang === 'fr' ? 'Oui' : 
                          document.documentElement.lang === 'es' ? 'Sí' : 'Yes'}
                    </button>
                    <button class="btn-no" onclick="this.parentElement.parentElement.parentElement.__callback(false)">
                        ${document.documentElement.lang === 'fr' ? 'Non' : 'No'}
                    </button>
                </div>
            </div>
        `;
        
        modal.innerHTML = content;
        modal.__callback = callback;
        
        return modal;
    }
}

// Styles pour la modale
const style = document.createElement('style');
style.textContent = `
    .membership-guide-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    .membership-guide-modal .modal-content {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        text-align: center;
        max-width: 90%;
        width: 400px;
    }

    .membership-guide-modal .button-group {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .membership-guide-modal button {
        padding: 0.5rem 2rem;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .membership-guide-modal .btn-yes {
        background: #47bae7;
        color: white;
    }

    .membership-guide-modal .btn-no {
        background: #f5f5f5;
        color: #333;
    }

    .membership-guide-modal button:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
`;

document.head.appendChild(style);

// Initialisation et export
window.membershipGuide = new MembershipGuide();
