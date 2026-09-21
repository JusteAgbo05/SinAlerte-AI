import React, { useState } from 'react';
import { createRoot } from 'react-dom/client';
import './style.css';

const Icon = ({ name, size = 20 }) => {
  const paths = {
    drop: <><path d="M12 2S5 10.1 5 15a7 7 0 0 0 14 0c0-4.9-7-13-7-13Z"/><path d="M8.5 15.5c.3 1.6 1.5 2.7 3.2 3"/></>,
    pin: <><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></>,
    camera: <><path d="M4 7h3l1.4-2h7.2L17 7h3a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z"/><circle cx="12" cy="13" r="3.5"/></>,
    mic: <><rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5.5 11a6.5 6.5 0 0 0 13 0M12 17.5V21M8.5 21h7"/></>,
    arrow: <><path d="M5 12h14M13 6l6 6-6 6"/></>,
    check: <path d="m5 12 4.2 4.2L19 6.5"/>,
    alert: <><path d="M10.4 3.5 2.1 18a2 2 0 0 0 1.7 3h16.4a2 2 0 0 0 1.7-3L13.6 3.5a1.85 1.85 0 0 0-3.2 0Z"/><path d="M12 9v4M12 17h.01"/></>,
    chart: <><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></>,
    users: <><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></>,
    menu: <><path d="M4 7h16M4 12h16M4 17h16"/></>,
    close: <><path d="m6 6 12 12M18 6 6 18"/></>,
    bell: <><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></>,
    map: <><path d="m9 18-6 3V6l6-3 6 3 6-3v15l-6 3-6-3Z"/><path d="M9 3v15M15 6v15"/></>,
    clock: <><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></>,
    wrench: <path d="M14.7 6.3a5 5 0 0 0-6.5 6.5L3 18a2.1 2.1 0 1 0 3 3l5.2-5.2a5 5 0 0 0 6.5-6.5l-3.1 3.1-3.2-1.1-1.1-3.2 3.1-3.1a5 5 0 0 0 1.3 1.3Z"/>,
    info: <><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></>,
    chevron: <path d="m9 18 6-6-6-6"/>,
  };
  return <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">{paths[name]}</svg>;
};

function App() {
  const [view, setView] = useState('home');
  const [lang, setLang] = useState('Français');
  const [recording, setRecording] = useState(false);
  const [submitted, setSubmitted] = useState(false);
  const [activeAlert, setActiveAlert] = useState('SA-2026-048');

  const goReport = () => { setView('report'); setSubmitted(false); };
  return <main>
    <header className="topbar">
      <button className="brand" onClick={() => setView('home')}><span className="brand-mark"><Icon name="drop" size={24}/></span><span>SìnAlerte <b>AI</b></span></button>
      <div className="desktop-nav"><button onClick={goReport}>Signaler une fuite</button><button onClick={() => setView('track')}>Suivre mon signalement</button><button className="portal" onClick={() => setView('dashboard')}>Espace opérateur <Icon name="arrow" size={16}/></button></div>
      <button className="mobile-menu"><Icon name="menu"/></button>
    </header>
    {view === 'home' && <Home onReport={goReport} onDashboard={() => setView('dashboard')} />}
    {view === 'report' && <Report lang={lang} setLang={setLang} recording={recording} setRecording={setRecording} submitted={submitted} setSubmitted={setSubmitted} onTrack={() => setView('track')} />}
    {view === 'track' && <Track onReport={goReport} />}
    {view === 'dashboard' && <Dashboard activeAlert={activeAlert} setActiveAlert={setActiveAlert} />}
  </main>;
}

const Home = ({ onReport, onDashboard }) => <>
  <section className="hero">
    <div className="hero-copy"><div className="eyebrow"><span></span> Ensemble, préservons l'eau</div><h1>Une fuite signalée.<br/><em>Une action lancée.</em></h1><p>Une plateforme simple pour signaler les fuites d’eau et aider les équipes à intervenir là où c’est le plus urgent.</p><div className="hero-actions"><button className="primary" onClick={onReport}>Signaler une fuite <Icon name="arrow"/></button><button className="text-button" onClick={onDashboard}>Voir l’espace opérateur <Icon name="chevron" size={17}/></button></div><div className="trust"><div><b>Français & Fongbè</b><span>Votre voix compte</span></div><i></i><div><b>Photo & position</b><span>Un geste suffit</span></div></div></div>
    <div className="hero-visual"><div className="halo"></div><div className="leak-photo"><div className="photo-label"><span className="pulse"></span> Signalement actif</div><div className="water-ripple r1"></div><div className="water-ripple r2"></div><div className="pipe"></div><div className="puddle"></div></div><div className="floating-card score"><span className="mini-icon coral"><Icon name="alert" size={16}/></span><div><small>Priorité IA</small><strong>Intervention rapide</strong></div></div><div className="floating-card loc"><span className="mini-icon blue"><Icon name="pin" size={16}/></span><div><small>Localisé</small><strong>Cotonou, Zongo</strong></div></div></div>
  </section>
  <section className="how"><div className="section-intro"><span className="eyebrow"><span></span> Simple et utile</span><h2>Du signalement à la réparation</h2></div><div className="steps">{[["01","Vous signalez","Photo, position et note vocale - sans formulaire compliqué.",'camera'],["02","Nous comprenons","L’IA analyse les informations et aide à évaluer l’urgence.",'chart'],["03","Ils interviennent","Une équipe est orientée et vous pouvez suivre la suite.",'wrench']].map(([n,t,d,i])=><article className="step" key={n}><div className="step-head"><span>{n}</span><Icon name={i}/></div><h3>{t}</h3><p>{d}</p></article>)}</div></section>
  <footer>© 2026 SìnAlerte AI <span>•</span> Protéger chaque goutte, ensemble.</footer>
</>;

function Report({ lang, setLang, recording, setRecording, submitted, setSubmitted, onTrack }) {
  if(submitted) return <section className="success-page"><div className="success-icon"><Icon name="check" size={38}/></div><span className="eyebrow"><span></span> Signalement envoyé</span><h1>Merci pour votre vigilance.</h1><p>Votre alerte <b>SA-2026-051</b> a été transmise. Elle sera analysée et priorisée par les équipes.</p><div className="status-card"><div><span>Statut actuel</span><b>Analyse en cours</b></div><Icon name="chart" size={25}/></div><button className="primary" onClick={onTrack}>Suivre mon signalement <Icon name="arrow"/></button><button className="link-btn" onClick={()=>setSubmitted(false)}>Signaler une autre fuite</button></section>;
  return <section className="report-page"><div className="report-heading"><button className="back" onClick={()=>history.back()}>←</button><div><span className="eyebrow"><span></span> Nouveau signalement</span><h1>Que se passe-t-il ?</h1><p>Quelques informations suffisent. Vous pourrez envoyer votre alerte en moins d’une minute.</p></div></div><div className="progress"><b>1</b><i></i><span>2</span><i></i><span>3</span><small>Informations</small></div><div className="report-grid"><div className="form-card"><label>Photo de la fuite <em>Recommandée</em></label><button className="upload"><Icon name="camera" size={27}/><b>Ajouter une photo</b><span>Prendre une photo ou choisir depuis la galerie</span></button><label>Votre position <em>Recommandée</em></label><button className="location-row"><span className="mini-icon green"><Icon name="pin"/></span><span><b>Utiliser ma position actuelle</b><small>Elle aide l’équipe à trouver la fuite</small></span><Icon name="chevron"/></button><label>Décrivez la situation <em>Optionnel</em></label><div className="language"><span>Langue de votre note vocale</span><div>{['Français','Fongbè'].map(x=><button key={x} onClick={()=>setLang(x)} className={lang===x?'selected':''}>{x}</button>)}</div></div><button className={'record '+(recording?'recording':'')} onClick={()=>setRecording(!recording)}><span><Icon name={recording?'close':'mic'} size={23}/></span><b>{recording?'Arrêter l’enregistrement':'Enregistrer une note vocale'}</b><small>{recording?'00:12 • Appuyez pour terminer':`Parlez en ${lang}`}</small></button><button className="primary full" onClick={()=>setSubmitted(true)}>Envoyer mon signalement <Icon name="arrow"/></button></div><aside className="privacy"><Icon name="info"/><h3>Vos informations sont protégées</h3><p>Nous utilisons votre position et vos médias uniquement pour traiter votre signalement. Aucun compte n’est nécessaire.</p></aside></div></section>
}

const Track = ({ onReport }) => <section className="track-page"><span className="eyebrow"><span></span> Suivi citoyen</span><h1>Votre signalement<br/><em>est pris en charge.</em></h1><p>SA-2026-048 · Rue 230, Zongo, Cotonou</p><div className="track-card"><div className="track-top"><div><span>État de l’alerte</span><h2>Équipe en route</h2></div><span className="chip blue-chip">Priorité élevée</span></div><div className="timeline"><div className="done"><i><Icon name="check" size={14}/></i><div><b>Signalement reçu</b><span>Aujourd’hui, 09:42</span></div></div><div className="done"><i><Icon name="check" size={14}/></i><div><b>Analyse et priorité confirmées</b><span>Aujourd’hui, 09:46</span></div></div><div className="current"><i><Icon name="wrench" size={15}/></i><div><b>Équipe envoyée</b><span>Arrivée estimée dans 25 minutes</span></div></div><div><i></i><div><b>Intervention terminée</b><span>Vous recevrez une confirmation</span></div></div></div><div className="team-note"><span className="avatar">AM</span><p><b>Équipe Aqua Maintenance</b><br/>a été informée de votre signalement.</p><Icon name="bell"/></div></div><button className="link-btn" onClick={onReport}>Signaler une autre fuite</button></section>;

const alerts = [{id:'SA-2026-048', title:'Fuite visible sur conduite', place:'Zongo, Cotonou', score:89, status:'À affecter', color:'red'}, {id:'SA-2026-047',title:'Eau stagnante près du marché',place:'Marché Dantokpa',score:74,status:'À affecter',color:'orange'}, {id:'SA-2026-046',title:'Compteur endommagé',place:'Cadjèhoun, Cotonou',score:62,status:'En route',color:'yellow'}];
function Dashboard({ activeAlert, setActiveAlert }) { const active = alerts.find(a=>a.id===activeAlert) || alerts[0]; return <section className="operator"><aside className="side"><button className="brand"><span className="brand-mark"><Icon name="drop" size={22}/></span><span>SìnAlerte <b>AI</b></span></button><div className="operator-label">ESPACE OPÉRATEUR</div>{[['chart','Vue d’ensemble'],['alert','Signalements'],['map','Carte & tournées'],['users','Équipes']].map(([i,t],idx)=><button className={'side-link '+(idx===1?'active':'')} key={t}><Icon name={i}/>{t}{idx===1&&<b>8</b>}</button>)}<div className="side-bottom"><button className="side-link"><Icon name="info"/>Guide opérateur</button><div className="profile"><span>AO</span><div><b>Amélie O.</b><small>Superviseure</small></div></div></div></aside><div className="dash-main"><div className="dash-header"><div><span className="eyebrow"><span></span> Lundi 21 septembre</span><h1>Bonjour, Amélie</h1><p>Voici ce qui demande votre attention aujourd’hui.</p></div><button className="notification"><Icon name="bell"/><b>3</b></button></div><div className="metrics"><Metric icon="alert" v="8" l="Nouveaux signalements" trend="+3 depuis ce matin" c="red"/><Metric icon="clock" v="24 min" l="Délai moyen de prise en charge" trend="-12% cette semaine" c="green"/><Metric icon="drop" v="12 400 L" l="Pertes estimées évitées" trend="Ce mois-ci" c="blue"/></div><div className="dash-content"><section className="alert-list"><div className="section-title"><div><h2>À prioriser</h2><p>Signalements analysés par l’IA, à valider.</p></div><button>Voir tout <Icon name="arrow" size={15}/></button></div><div className="filters"><button className="filter-active">Tous <b>8</b></button><button>Urgents <b>3</b></button><button>À vérifier <b>2</b></button></div>{alerts.map(a=><button className={'alert-row '+(a.id===activeAlert?'selected-row':'')} onClick={()=>setActiveAlert(a.id)} key={a.id}><span className={'severity '+a.color}></span><div><b>{a.title}</b><small><Icon name="pin" size={13}/>{a.place} · il y a 18 min</small></div><div className="alert-score"><b>{a.score}</b><span>Score IA</span></div><Icon name="chevron" size={17}/></button>)}</section><section className="map-panel"><div className="map-top"><span><Icon name="map"/> Carte des alertes</span><button>Afficher les tournées</button></div><div className="fake-map"><div className="roads r-a"></div><div className="roads r-b"></div><div className="roads r-c"></div><div className="zone z1"></div><div className="zone z2"></div><span className="map-dot d1 red"><Icon name="alert" size={14}/></span><span className="map-dot d2 orange"></span><span className="map-dot d3 yellow"></span><span className="map-label">COTONOU</span><div className="map-legend"><i className="red"></i> Élevée <i className="orange"></i> Moyenne</div></div><div className="decision"><div className="decision-head"><span className="severity red"></span><div><b>{active.id} · {active.title}</b><small><Icon name="pin" size={13}/>{active.place}</small></div><span className="chip red-chip">Priorité {active.score}/100</span></div><div className="ai-note"><Icon name="chart"/><span><b>Pourquoi cette priorité ?</b><small>Fort débit estimé · Zone fréquentée · Conduite principale à proximité</small></span><Icon name="info" size={17}/></div><div className="assign"><div><span>Équipe suggérée</span><b>Aqua Maintenance · Équipe 2</b><small>À 1,4 km · Disponible maintenant</small></div><button className="primary small">Affecter <Icon name="arrow" size={16}/></button></div></div></section></div></div></section> }
const Metric = ({icon,v,l,trend,c}) => <article className="metric"><span className={'metric-icon '+c}><Icon name={icon}/></span><div><strong>{v}</strong><p>{l}</p><small>{trend}</small></div></article>;

createRoot(document.getElementById('root')).render(<App/>);
