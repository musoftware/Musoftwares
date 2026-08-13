import React, { useEffect, useRef, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import * as THREE from 'three';
import { SVGLoader } from 'three/examples/jsm/loaders/SVGLoader.js';
import gsap from 'gsap';
import { __ } from '@/lib/i18n';

interface DashboardProps {
    stats: any;
    recentTransactions: any[];
    chartData: any[];
    activeToolLicenses: any[];
    userProjects: any[];
    realNotifications: any[];
    authUser: any;
    userBalanceVal: number;
    currencySymbol: string;
    userBalanceFormatted: string;
    userPoints: number;
    unpaidInvoices: any[];
    unpaidCount: number;
    unpaidAmount: number;
    totalDueAmount: number;
    totalDueFormatted: string;
}

export default function Dashboard({
    stats,
    recentTransactions,
    chartData,
    activeToolLicenses,
    userProjects,
    realNotifications,
    authUser,
    userBalanceVal,
    currencySymbol,
    userBalanceFormatted,
    userPoints,
    unpaidInvoices,
    unpaidCount,
    unpaidAmount,
    totalDueAmount,
    totalDueFormatted,
}: DashboardProps) {
    const logoRef = useRef<HTMLDivElement>(null);
    const sceneRef = useRef<THREE.Scene | null>(null);
    const rendererRef = useRef<THREE.WebGLRenderer | null>(null);
    const corePointLightRef = useRef<THREE.PointLight | null>(null);
    const coreGroupRef = useRef<THREE.Group | null>(null);
    const logo3DGroupRef = useRef<THREE.Group | null>(null);
    const vertexNodesRef = useRef<THREE.Points | null>(null);
    const ring1GroupRef = useRef<THREE.Group | null>(null);
    const ring2GroupRef = useRef<THREE.Group | null>(null);
    const ring3MeshRef = useRef<THREE.Mesh | null>(null);
    const neonAccentMatRef = useRef<THREE.MeshBasicMaterial | null>(null);

    // React Preloader state
    const [welcomeSuppressed, setWelcomeSuppressed] = useState(() => {
        try {
            const skipUntil = parseInt(localStorage.getItem('v8_skip_welcome_until') || '0', 10);
            return Date.now() < skipUntil;
        } catch (e) {
            return false;
        }
    });
    const [isLoading, setIsLoading] = useState(!welcomeSuppressed);
    const [audioAllowed, setAudioAllowed] = useState<boolean | null>(null);
    const [introText, setIntroText] = useState('');
    const [outText, setOutText] = useState('');
    const [showWelcomeImg, setShowWelcomeImg] = useState(false);
    const [audioMuted, setAudioMuted] = useState(false);
    const bgAudioRef = useRef<HTMLAudioElement | null>(null);

    // Layout Modals & Interactive UI States
    const [coreGlow, setCoreGlow] = useState<string | null>(null);
    const [isPayDueOpen, setIsPayDueOpen] = useState(false);
    const [isCommandBarOpen, setIsCommandBarOpen] = useState(false);
    const [commandQuery, setCommandQuery] = useState('');

    // Load static styles dynamically
    useEffect(() => {
        const bootstrapLink = document.createElement('link');
        bootstrapLink.rel = 'stylesheet';
        bootstrapLink.href = '/v8main/css/bootstrap.min.css';
        bootstrapLink.id = 'v8main-bootstrap';

        const styleLink = document.createElement('link');
        styleLink.rel = 'stylesheet';
        styleLink.href = '/v8main/css/style.css?v=1.1';
        styleLink.id = 'v8main-style';

        document.head.appendChild(bootstrapLink);
        document.head.appendChild(styleLink);

        return () => {
            document.getElementById('v8main-bootstrap')?.remove();
            document.getElementById('v8main-style')?.remove();
        };
    }, []);

    // Automatic fluid viewport scaling
    useEffect(() => {
        const applySmartViewportScaling = () => {
            const w = window.innerWidth;
            const h = window.innerHeight;

            if (w <= 768) {
                document.body.style.zoom = '0.70';
                return;
            }

            if (w <= 1365) {
                document.body.style.zoom = '0.80';
                return;
            }

            const scaleX = w / 1366;
            const scaleY = h / 850;
            const computedScale = Math.min(scaleX, scaleY) * 0.90;
            const finalZoom = Math.min(1.45, Math.max(0.90, computedScale)).toFixed(3);
            document.body.style.zoom = finalZoom;
        };

        applySmartViewportScaling();
        window.addEventListener('resize', applySmartViewportScaling);
        return () => {
            window.removeEventListener('resize', applySmartViewportScaling);
            document.body.style.zoom = '';
        };
    }, []);

    // Global Key Listening (Ctrl + K Command bar)
    useEffect(() => {
        const handleKeyDown = (e: KeyboardEvent) => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                setIsCommandBarOpen(prev => !prev);
            }
            if (e.key === 'Escape') {
                setIsCommandBarOpen(false);
                setIsPayDueOpen(false);
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, []);

    // Typewriter preloader engine
    useEffect(() => {
        if (!isLoading || audioAllowed === null) return;

        let active = true;
        let welcomeAudio: HTMLAudioElement | null = null;

        if (audioAllowed) {
            welcomeAudio = new Audio('/v8main/sound/welcome.mp3');
            welcomeAudio.play().catch(e => console.log('Audio welcome track blocked', e));
        }

        const userName = authUser?.name ?? 'User';
        const str1 = `Hello, ${userName}...`;
        const str2 = 'Welcome back!';

        let i = 0;
        const type1 = () => {
            if (!active) return;
            if (i <= str1.length) {
                setIntroText(str1.slice(0, i));
                i++;
                setTimeout(type1, 80);
            } else {
                setShowWelcomeImg(true);
                setTimeout(startType2, 1000);
            }
        };

        const startType2 = () => {
            let j = 0;
            const type2 = () => {
                if (!active) return;
                if (j <= str2.length) {
                    setOutText(str2.slice(0, j));
                    j++;
                    setTimeout(type2, 80);
                } else {
                    setTimeout(() => {
                        if (active) {
                            setIsLoading(false);
                            try {
                                localStorage.setItem('v8_skip_welcome_until', (Date.now() + 30 * 24 * 60 * 60 * 1000).toString());
                            } catch (e) { /* empty */ }

                            if (audioAllowed) {
                                const loopAudio = new Audio('/v8main/sound/sound-all-time.mp3');
                                loopAudio.loop = true;
                                loopAudio.play().catch(e => console.log('Loop audio track blocked', e));
                                bgAudioRef.current = loopAudio;
                            }
                        }
                    }, 1200);
                }
            };
            type2();
        };

        type1();

        return () => {
            active = false;
            if (welcomeAudio) {
                welcomeAudio.pause();
            }
        };
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [isLoading, audioAllowed]);

    // Background soundtrack toggler
    const toggleMute = () => {
        if (bgAudioRef.current) {
            if (audioMuted) {
                bgAudioRef.current.play().catch(e => console.log(e));
                setAudioMuted(false);
            } else {
                bgAudioRef.current.pause();
                setAudioMuted(true);
            }
        }
    };

    const handleSkipIntro = () => {
        try {
            localStorage.setItem('v8_skip_welcome_until', (Date.now() + 30 * 24 * 60 * 60 * 1000).toString());
        } catch (e) { /* empty */ }
        setIsLoading(false);
        if (audioAllowed) {
            const loopAudio = new Audio('/v8main/sound/sound-all-time.mp3');
            loopAudio.loop = true;
            loopAudio.play().catch(e => console.log(e));
            bgAudioRef.current = loopAudio;
        }
    };

    // Three.js WebGL Core logic
    useEffect(() => {
        if (isLoading) return;
        const container = logoRef.current;
        if (!container) return;

        // Clean previous runs
        const oldCanvas = document.getElementById('three-hologram-canvas');
        if (oldCanvas) oldCanvas.remove();

        const canvas = document.createElement('canvas');
        canvas.id = 'three-hologram-canvas';
        canvas.style.position = 'absolute';
        canvas.style.top = '0';
        canvas.style.left = '0';
        canvas.style.width = '100%';
        canvas.style.height = '100%';
        canvas.style.pointerEvents = 'none';
        canvas.style.zIndex = '5';
        container.style.position = 'relative';
        container.appendChild(canvas);

        const width = container.clientWidth || 286;
        const height = container.clientHeight || 285;

        const scene = new THREE.Scene();
        sceneRef.current = scene;

        const camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 1000);
        camera.position.z = 210;

        const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
        renderer.setSize(width, height);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        rendererRef.current = renderer;

        const coreGroup = new THREE.Group();
        scene.add(coreGroup);
        coreGroupRef.current = coreGroup;

        // Titanium Chrome Logo finish
        const logo3DGroup = new THREE.Group();
        coreGroup.add(logo3DGroup);
        logo3DGroupRef.current = logo3DGroup;

        const chromeMaterial = new THREE.MeshPhongMaterial({
            color: 0x3a3f4d,
            emissive: 0x090814,
            specular: 0xffffff,
            shininess: 240,
            side: THREE.DoubleSide,
        });

        // Connected MU fallback logo
        const createFallback3DMULogo = () => {
            const mShape = new THREE.Shape();
            mShape.moveTo(-36, 30);
            mShape.lineTo(-36, -30);
            mShape.lineTo(-24, -30);
            mShape.lineTo(-24, 8);
            mShape.lineTo(-12, -18);
            mShape.lineTo(0, 8);
            mShape.lineTo(0, -30);
            mShape.lineTo(12, -30);
            mShape.lineTo(12, 30);
            mShape.lineTo(0, 30);
            mShape.lineTo(-12, 4);
            mShape.lineTo(-24, 30);
            mShape.closePath();

            const uShape = new THREE.Shape();
            uShape.moveTo(14, 30);
            uShape.lineTo(24, -22);
            uShape.lineTo(34, -30);
            uShape.lineTo(44, -22);
            uShape.lineTo(54, 30);
            uShape.lineTo(42, 30);
            uShape.lineTo(34, -10);
            uShape.lineTo(26, 30);
            uShape.closePath();

            const settings = { depth: 14, bevelEnabled: true, bevelThickness: 3, bevelSize: 1.5, bevelSegments: 4 };
            const mGeo = new THREE.ExtrudeGeometry(mShape, settings);
            const uGeo = new THREE.ExtrudeGeometry(uShape, settings);
            mGeo.center();
            uGeo.center();

            const mMesh = new THREE.Mesh(mGeo, chromeMaterial);
            const uMesh = new THREE.Mesh(uGeo, chromeMaterial);
            mMesh.position.x = -16;
            uMesh.position.x = 22;

            logo3DGroup.add(mMesh);
            logo3DGroup.add(uMesh);
        };

        // Load SVG Favicon
        const svgLoader = new SVGLoader();
        svgLoader.load('/favicon.svg', (data) => {
            const paths = data.paths;
            const svgGroup = new THREE.Group();

            for (let i = 0; i < paths.length; i++) {
                const path = paths[i];
                if (i === 0 && paths.length > 1) continue; // Skip circular backdrop disc

                const shapes = SVGLoader.createShapes(path);
                for (let j = 0; j < shapes.length; j++) {
                    const shape = shapes[j];
                    const extrudeGeo = new THREE.ExtrudeGeometry(shape, {
                        depth: 16,
                        bevelEnabled: true,
                        bevelThickness: 3.5,
                        bevelSize: 1.5,
                        bevelSegments: 4,
                    });
                    extrudeGeo.center();

                    const mesh = new THREE.Mesh(extrudeGeo, chromeMaterial);
                    svgGroup.add(mesh);
                }
            }

            svgGroup.scale.set(0.32, -0.32, 0.32);
            logo3DGroup.add(svgGroup);
        }, undefined, () => {
            createFallback3DMULogo();
        });

        // Glowing Core light
        const corePointLight = new THREE.PointLight(0xa855f7, 1.2, 300);
        coreGroup.add(corePointLight);
        corePointLightRef.current = corePointLight;

        // Floating geodetic nodes
        const wireGeo = new THREE.IcosahedronGeometry(45, 2);
        const wirePositions = wireGeo.attributes.position.array as Float32Array;
        const vertexGeo = new THREE.BufferGeometry();
        vertexGeo.setAttribute('position', new THREE.BufferAttribute(wirePositions, 3));
        const vertexMat = new THREE.PointsMaterial({
            color: 0xc084fc,
            size: 3.2,
            transparent: true,
            opacity: 0.9,
            blending: THREE.AdditiveBlending,
        });
        const vertexNodes = new THREE.Points(vertexGeo, vertexMat);
        coreGroup.add(vertexNodes);
        vertexNodesRef.current = vertexNodes;

        // Concentric Mechanical Outer Rings
        const mechanicalMat = new THREE.MeshPhongMaterial({
            color: 0x160c29,
            specular: 0x8a4fff,
            shininess: 80,
            emissive: 0x1a0c33,
            side: THREE.DoubleSide,
        });

        const neonAccentMat = new THREE.MeshBasicMaterial({
            color: 0x8a4fff,
            transparent: true,
            opacity: 0.85,
            blending: THREE.AdditiveBlending,
        });
        neonAccentMatRef.current = neonAccentMat;

        // Segmented Ring 1
        const ring1Group = new THREE.Group();
        const torusGeo1 = new THREE.TorusGeometry(54, 2.2, 16, 48);
        const torusMesh1 = new THREE.Mesh(torusGeo1, mechanicalMat);
        ring1Group.add(torusMesh1);

        for (let i = 0; i < 6; i++) {
            const clampGeo = new THREE.BoxGeometry(4, 7, 7);
            const clampMesh = new THREE.Mesh(clampGeo, mechanicalMat);
            const angle = (i / 6) * Math.PI * 2;
            clampMesh.position.set(Math.cos(angle) * 54, Math.sin(angle) * 54, 0);
            clampMesh.rotation.z = angle;
            ring1Group.add(clampMesh);

            const slotGeo = new THREE.BoxGeometry(2, 5, 7.5);
            const slotMesh = new THREE.Mesh(slotGeo, neonAccentMat);
            slotMesh.position.copy(clampMesh.position);
            slotMesh.rotation.z = angle;
            ring1Group.add(slotMesh);
        }
        coreGroup.add(ring1Group);
        ring1GroupRef.current = ring1Group;

        // Mechanical Segmented Ring 2 (Counter Rotating)
        const ring2Group = new THREE.Group();
        const torusGeo2 = new THREE.TorusGeometry(63, 3.5, 16, 36);
        const torusMesh2 = new THREE.Mesh(torusGeo2, mechanicalMat);
        ring2Group.add(torusMesh2);

        for (let j = 0; j < 8; j++) {
            const armorGeo = new THREE.BoxGeometry(6, 9, 9);
            const armorMesh = new THREE.Mesh(armorGeo, mechanicalMat);
            const angle2 = (j / 8) * Math.PI * 2;
            armorMesh.position.set(Math.cos(angle2) * 63, Math.sin(angle2) * 63, 0);
            armorMesh.rotation.z = angle2;
            ring2Group.add(armorMesh);

            const stripGeo = new THREE.BoxGeometry(3, 7, 9.6);
            const stripMesh = new THREE.Mesh(stripGeo, neonAccentMat);
            stripMesh.position.copy(armorMesh.position);
            stripMesh.rotation.z = angle2;
            ring2Group.add(stripMesh);
        }
        ring2Group.rotation.x = Math.PI / 3;
        ring2Group.rotation.y = Math.PI / 6;
        coreGroup.add(ring2Group);
        ring2GroupRef.current = ring2Group;

        // Orbital Ring 3
        const ring3Geo = new THREE.TorusGeometry(72, 1.0, 16, 64);
        const ring3Mat = new THREE.MeshBasicMaterial({ color: 0xc084fc, transparent: true, opacity: 0.5 });
        const ring3Mesh = new THREE.Mesh(ring3Geo, ring3Mat);
        ring3Mesh.rotation.x = -Math.PI / 4;
        coreGroup.add(ring3Mesh);
        ring3MeshRef.current = ring3Mesh;

        // Levitation Underglow Disc
        const underglowGeo = new THREE.RingGeometry(0, 42, 32);
        const underglowMat = new THREE.MeshBasicMaterial({
            color: 0x8a4fff,
            transparent: true,
            opacity: 0.35,
            blending: THREE.AdditiveBlending,
            side: THREE.DoubleSide,
        });
        const underglowMesh = new THREE.Mesh(underglowGeo, underglowMat);
        underglowMesh.position.y = -68;
        underglowMesh.rotation.x = Math.PI / 2;
        scene.add(underglowMesh);

        // Lights
        const ambientLight = new THREE.AmbientLight(0x1a0c33, 2.0);
        scene.add(ambientLight);

        const dirLight1 = new THREE.DirectionalLight(0x8a4fff, 2.5);
        dirLight1.position.set(120, 120, 150);
        scene.add(dirLight1);

        const dirLight2 = new THREE.DirectionalLight(0xc084fc, 1.5);
        dirLight2.position.set(-120, -80, -100);
        scene.add(dirLight2);

        // Mouse Drag & Parallax Spin momentum
        let mouseX = 0, mouseY = 0;
        const handleMouseMove = (e: MouseEvent) => {
            const windowHalfX = window.innerWidth / 2;
            const windowHalfY = window.innerHeight / 2;
            mouseX = (e.clientX - windowHalfX) * 0.0007;
            mouseY = (e.clientY - windowHalfY) * 0.0007;
        };
        window.addEventListener('mousemove', handleMouseMove);

        canvas.style.pointerEvents = 'auto';
        canvas.style.cursor = 'grab';

        let isDragging = false;
        let previousMousePosition = { x: 0, y: 0 };
        let spinVelocity = { x: 0, y: 0 };

        const onPointerDown = (e: any) => {
            isDragging = true;
            canvas.style.cursor = 'grabbing';
            const pageX = e.touches ? e.touches[0].clientX : e.clientX;
            const pageY = e.touches ? e.touches[0].clientY : e.clientY;
            previousMousePosition = { x: pageX, y: pageY };
            spinVelocity = { x: 0, y: 0 };
        };

        const onPointerMove = (e: any) => {
            if (!isDragging) return;
            const pageX = e.touches ? e.touches[0].clientX : e.clientX;
            const pageY = e.touches ? e.touches[0].clientY : e.clientY;

            const deltaX = pageX - previousMousePosition.x;
            const deltaY = pageY - previousMousePosition.y;

            coreGroup.rotation.y += deltaX * 0.015;
            coreGroup.rotation.x += deltaY * 0.015;

            spinVelocity.y = deltaX * 0.008;
            spinVelocity.x = deltaY * 0.008;

            previousMousePosition = { x: pageX, y: pageY };
        };

        const onPointerUp = () => {
            if (isDragging) {
                isDragging = false;
                canvas.style.cursor = 'grab';
            }
        };

        canvas.addEventListener('mousedown', onPointerDown);
        canvas.addEventListener('touchstart', onPointerDown, { passive: true });
        window.addEventListener('mousemove', onPointerMove);
        window.addEventListener('touchmove', onPointerMove, { passive: true });
        window.addEventListener('mouseup', onPointerUp);
        window.addEventListener('touchend', onPointerUp);

        // GSAP Start Choreography
        gsap.set('.hud-dashboard-gauge', { opacity: 0, scale: 0.1, rotation: -180 });
        gsap.set('.dashboard-side-gauges', { opacity: 0, y: 20 });
        gsap.set('.orbital-left .orbital-node', { opacity: 0, x: -60 });
        gsap.set('.orbital-right .orbital-node', { opacity: 0, x: 60 });
        gsap.set('.orbital-identity', { opacity: 0, scale: 0.8 });

        const tl = gsap.timeline();
        tl.to('.hud-dashboard-gauge', { opacity: 1, scale: 0.9, rotation: 0, duration: 1.0, ease: 'back.out(1.7)' })
          .to('.orbital-identity', { opacity: 1, scale: 1, duration: 0.6, ease: 'power2.out' }, '-=0.6')
          .to('.dashboard-side-gauges', { opacity: 1, y: 0, duration: 0.6, ease: 'power2.out' }, '-=0.4')
          .to('.gauge-bar-fill', {
              width: (index, target) => target.getAttribute('data-target-width') || '0%',
              duration: 1.0,
              ease: 'power2.out',
          }, '-=0.3')
          .to('.orbital-left .orbital-node', { opacity: 1, x: 0, duration: 0.5, stagger: 0.06, ease: 'power2.out' }, '-=0.8')
          .to('.orbital-right .orbital-node', { opacity: 1, x: 0, duration: 0.5, stagger: 0.06, ease: 'power2.out' }, '-=0.8');

        // Main WebGL animation loop
        const clock = new THREE.Clock();
        let animationFrameId: number;

        const animate = () => {
            animationFrameId = requestAnimationFrame(animate);
            const elapsedTime = clock.getElapsedTime();

            corePointLight.intensity = 1.0 + Math.sin(elapsedTime * 2.0) * 0.25;

            if (logo3DGroup) logo3DGroup.rotation.y += 0.008;
            if (vertexNodes) {
                vertexNodes.rotation.y += 0.005;
                vertexNodes.rotation.z += 0.002;
            }

            ring1Group.rotation.z += 0.009;
            ring2Group.rotation.z -= 0.012;
            ring3Mesh.rotation.z += 0.006;

            if (!isDragging) {
                coreGroup.rotation.y += spinVelocity.y;
                coreGroup.rotation.x += spinVelocity.x;

                spinVelocity.y *= 0.95;
                spinVelocity.x *= 0.95;

                coreGroup.rotation.y += (mouseX - coreGroup.rotation.y) * 0.03;
                coreGroup.rotation.x += (mouseY - coreGroup.rotation.x) * 0.03;
            }

            coreGroup.position.y = Math.sin(elapsedTime * 1.8) * 3;
            renderer.render(scene, camera);
        };
        animate();

        // Cleanup WebGL resources
        return () => {
            cancelAnimationFrame(animationFrameId);
            window.removeEventListener('mousemove', handleMouseMove);
            canvas.removeEventListener('mousedown', onPointerDown);
            canvas.removeEventListener('touchstart', onPointerDown);
            window.removeEventListener('mousemove', onPointerMove);
            window.removeEventListener('touchmove', onPointerMove);
            window.removeEventListener('mouseup', onPointerUp);
            window.removeEventListener('touchend', onPointerUp);
            renderer.dispose();
            scene.clear();
        };
    }, [isLoading]);

    // Handle node hovers (sounds & glows)
    const playHoverSound = () => {
        if (audioAllowed && !audioMuted) {
            new Audio('/v8main/sound/hover-icons.mp3').play().catch(() => {});
        }
    };
    const playClickSound = () => {
        if (audioAllowed && !audioMuted) {
            new Audio('/v8main/sound/basic-icons-hover.mp3').play().catch(() => {});
        }
    };

    const handleNodeHoverStart = (themeName: string) => {
        playHoverSound();
        setCoreGlow(themeName);
        if (neonAccentMatRef.current && coreGroupRef.current) {
            neonAccentMatRef.current.opacity = 1.0;
            coreGroupRef.current.scale.set(1.08, 1.08, 1.08);
        }
    };

    const handleNodeHoverEnd = () => {
        setCoreGlow(null);
        if (neonAccentMatRef.current && coreGroupRef.current) {
            neonAccentMatRef.current.opacity = 0.85;
            coreGroupRef.current.scale.set(1.0, 1.0, 1.0);
        }
    };

    // Filter list for SPOTLIGHT search
    const spotlightItems = [
        { name: 'ERP Enterprise System', desc: 'Financial & Accounting Operations', icon: 'icon-calendar', href: '/sso/erp', type: 'Open' },
        { name: 'CRM Customer Management', desc: 'Leads & Client Interactions', icon: 'icon-users', href: '/sso/crm', type: 'Open' },
        { name: 'Gold Saver System', desc: 'Precious Metal Investment', icon: 'icon-star', href: '/sso/goldsaversys', type: 'Open' },
        { name: 'Affiliate POS System', desc: 'Partner Sales & Commissions', icon: 'icon-chart-bar', href: '/sso/affsys', type: 'Open' },
        { name: 'Booking System', desc: 'Reservations & Appointments', icon: 'icon-clock', href: '/sso/bookingsys', type: 'Open' },
        { name: 'Runtime Agent Tools', desc: 'AI & Automation Engines', icon: 'icon-cog', href: '/sso/toolsys', type: 'Open' },
        { name: 'Marketplace Services', desc: 'Addons & Plugins Catalog', icon: 'icon-social', href: '/marketplace/services', type: 'Open' },
        { name: 'Billing & Invoices', desc: 'Manage Payments', icon: 'icon-credit-card', href: '/billing/invoices', type: 'Open' },
    ];

    const filteredSpotlight = spotlightItems.filter(item =>
        item.name.toLowerCase().includes(commandQuery.toLowerCase()) ||
        item.desc.toLowerCase().includes(commandQuery.toLowerCase())
    );

    // Audio Boot request component
    if (isLoading && audioAllowed === null) {
        return (
            <div className="preloader-wrapper d-flex align-items-center justify-content-center" style={{ background: '#030e11', minHeight: '100vh' }}>
                <Head title="System Booting - Musoftwares" />
                <div className="text-center text-light p-4" style={{ maxWidth: '400px', background: 'rgba(13, 6, 26, 0.8)', border: '1px solid #8A4FFF', borderRadius: '16px', boxShadow: '0 0 30px rgba(138, 79, 255, 0.4)' }}>
                    <h5 className="font-weight-bold mb-3 text-cyan">SYSTEM INITIATION</h5>
                    <p className="small text-muted mb-4">Would you like to boot the dashboard with active holographic soundscapes?</p>
                    <div className="d-flex justify-content-center gap-3">
                        <button onClick={() => setAudioAllowed(false)} className="btn btn-outline-secondary px-4 py-2 text-light" style={{ borderRadius: '8px' }}>NO AUDIO</button>
                        <button onClick={() => setAudioAllowed(true)} className="btn btn-info px-4 py-2 font-weight-bold" style={{ borderRadius: '8px', background: 'linear-gradient(135deg, #8A4FFF, #a855f7)', border: 'none', color: '#fff' }}>ACTIVATE SOUND</button>
                    </div>
                </div>
            </div>
        );
    }

    // Typewriter preloader
    if (isLoading) {
        return (
            <div className="preloader-wrapper" style={{ background: '#030e11', height: '100%', width: '100%', position: 'fixed', top: 0, left: 0, zIndex: 9999999 }}>
                <Head title="Loading Dashboard - Musoftwares" />
                <button onClick={handleSkipIntro} className="btn btn-outline-info btn-sm skip-intro-now-btn position-absolute" style={{ top: '25px', right: '25px', zIndex: 99999, borderRadius: '20px', fontSize: '11px', backdropFilter: 'blur(10px)', color: '#00f0ff', borderColor: 'rgba(0, 240, 255, 0.4)' }}>
                    Skip Intro &#9889;
                </button>
                <div className="preloader" style={{ position: 'absolute', top: '50%', left: '50%', transform: 'translate(-50%, -50%)' }}>
                    {!showWelcomeImg ? (
                        <div className="loading-Recovered" />
                    ) : (
                        <div className="welcome-wrap">
                            <div className="welcome">
                                <img src={authUser?.avatar_url ?? '/v8main/img/user.jpg'} alt="" style={{ height: '119px', width: '119px', display: 'block', opacity: 0.5, objectFit: 'cover', position: 'relative', top: '18px', left: '18px', borderRadius: '50%' }} />
                            </div>
                        </div>
                    )}
                    <div className="intro text-light font-weight-bold text-center mt-3" style={{ minHeight: '24px' }}>{introText}</div>
                    <div className="out text-light mt-2 text-center" style={{ minHeight: '24px' }}>{outText}</div>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen relative" style={{ background: '#0d061a', color: '#f3e8ff' }}>
            <Head title={`${authUser?.name ?? 'Dashboard'} - Musoftwares`} />

            {/* Minimalist Top HUD Header */}
            <header className="nav sci-fi-hud-header d-flex align-items-center justify-content-between">
                <div className="container-fluid px-3">
                    <div className="d-flex align-items-center justify-content-between w-100">
                        {/* Logo Left */}
                        <div className="d-flex align-items-center pointer" onClick={() => router.visit('/dashboard')} style={{ cursor: 'pointer' }}>
                            <img className="logo" src="/favicon.svg" alt="Musoftware" style={{ height: '28px', width: '28px', filter: 'drop-shadow(0 0 10px rgba(168, 85, 247, 0.8))' }} />
                            <span className="brand-name-text ml-2" style={{ fontSize: '17px', fontWeight: 800, color: '#f3e8ff', letterSpacing: '0.5px', textShadow: '0 0 14px rgba(168, 85, 247, 0.7)' }}>Musoftware</span>
                        </div>

                        {/* Center Dials */}
                        <div className="d-none d-lg-flex align-items-center justify-content-center gap-3">
                            <Link href="/financial/add-balance" className="d-flex align-items-center px-3 py-1 text-decoration-none rounded-pill" style={{ background: 'rgba(16, 185, 129, 0.1)', border: '1px solid rgba(16, 185, 129, 0.35)', backdropFilter: 'blur(8px)' }}>
                                <i className="icon-wallet mr-2" style={{ color: '#10b981', fontSize: '13px' }}></i>
                                <span style={{ fontSize: '11px', fontWeight: 600, color: '#d1fae5', marginRight: '4px' }}>{__('dashboard.wallet')}:</span>
                                <span style={{ fontSize: '12px', fontWeight: 800, color: '#10b981', letterSpacing: '0.5px' }}>{userBalanceFormatted}</span>
                            </Link>

                            <div className="d-flex align-items-center px-3 py-1 rounded-pill ml-2" style={{ background: 'rgba(245, 158, 11, 0.1)', border: '1px solid rgba(245, 158, 11, 0.35)', backdropFilter: 'blur(8px)' }}>
                                <i className="icon-star mr-2" style={{ color: '#f59e0b', fontSize: '13px' }}></i>
                                <span style={{ fontSize: '11px', fontWeight: 600, color: '#fef3c7', marginRight: '4px' }}>{__('dashboard.points')}:</span>
                                <span style={{ fontSize: '12px', fontWeight: 800, color: '#f59e0b', letterSpacing: '0.5px' }}>{userPoints.toLocaleString()} {__('dashboard.pts')}</span>
                            </div>
                        </div>

                        {/* Controls Right */}
                        <div className="d-flex align-items-center">
                            {/* Soundtrack control */}
                            {audioAllowed && (
                                <button onClick={toggleMute} className={`btn mr-2 d-flex align-items-center justify-content-center pointer ${audioMuted ? 'text-muted' : 'text-info'}`} style={{ width: '34px', height: '34px', background: 'rgba(35, 16, 70, 0.6)', border: '1px solid rgba(168, 85, 247, 0.4)', borderRadius: '50%', outline: 'none' }} title="Toggle Background Music">
                                    <i className={audioMuted ? 'icon-volume-off' : 'icon-volume-up'} />
                                </button>
                            )}

                            {/* Search Trigger */}
                            <div onClick={() => setIsCommandBarOpen(true)} className="command-bar-trigger mr-2 d-flex align-items-center pointer px-2 py-1 rounded-pill" style={{ background: 'rgba(35, 16, 70, 0.6)', border: '1px solid rgba(168, 85, 247, 0.4)', backdropFilter: 'blur(8px)', cursor: 'pointer' }} title="Universal Spotlight Search (Ctrl + K)">
                                <i className="icon-search mr-1" style={{ fontSize: '12px', color: '#00f0ff' }}></i>
                                <span className="mr-2 d-none d-sm-inline" style={{ color: '#f3e8ff', fontSize: '11px', fontWeight: 600 }}>{__('dashboard.search')}</span>
                                <kbd className="command-bar-kbd" style={{ background: 'rgba(0, 240, 255, 0.15)', color: '#00f0ff', border: '1px solid rgba(0, 240, 255, 0.4)', fontSize: '9px', padding: '1px 5px', borderRadius: '4px' }}>Ctrl K</kbd>
                            </div>

                            {/* Notifications Dropdown */}
                            <div className="dropdown mr-2">
                                <div className="d-flex align-items-center justify-content-center pointer position-relative dropdown-toggle" id="notificationDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style={{ width: '34px', height: '34px', background: 'rgba(35, 16, 70, 0.6)', border: '1px solid rgba(168, 85, 247, 0.4)', borderRadius: '50%' }}>
                                    <i className="icon-bell" style={{ color: '#a855f7', fontSize: '13px' }}></i>
                                    {realNotifications.length > 0 && (
                                        <span className="status-dot-led position-absolute" style={{ top: '3px', right: '3px', width: '6px', height: '6px', backgroundColor: '#00f0ff', boxShadow: '0 0 8px #00f0ff' }}></span>
                                    )}
                                </div>
                                <div className="dropdown-menu dropdown-menu-right p-3" aria-labelledby="notificationDropdown" style={{ background: '#130924', border: '1px solid #8A4FFF', borderRadius: '12px', width: '290px', boxShadow: '0 15px 35px rgba(0,0,0,0.95), 0 0 20px rgba(138,79,255,0.35)' }}>
                                    <div className="d-flex align-items-center justify-content-between pb-2 mb-2" style={{ borderBottom: '1px solid rgba(138,79,255,0.25)' }}>
                                        <div className="font-weight-bold" style={{ color: '#f3e8ff', fontSize: '13px' }}>
                                            <i className="icon-bell mr-1" style={{ color: '#8A4FFF' }}></i> {__('dashboard.notifications')}
                                        </div>
                                        <Link href="/notifications" className="small font-weight-bold" style={{ color: '#a855f7' }}>{__('dashboard.view_all')}</Link>
                                    </div>
                                    <div className="notification-list text-left" style={{ maxHeight: '240px', overflowY: 'auto' }}>
                                        {realNotifications.map((notif, idx) => (
                                            <a key={idx} href={notif.link ?? '/notifications'} className="d-block p-2 mb-2 rounded position-relative text-decoration-none" style={{ background: 'rgba(138, 79, 255, 0.08)', borderLeft: '3px solid #8A4FFF' }}>
                                                <div className="d-flex align-items-center justify-content-between">
                                                    <div className="small font-weight-bold" style={{ color: '#f3e8ff', fontSize: '11px' }}>{notif.title}</div>
                                                    <span style={{ color: '#a855f7', fontSize: '8px' }}>{notif.time}</span>
                                                </div>
                                                <div style={{ color: '#d8b4fe', fontSize: '10px', lineHeight: 1.3 }} className="mt-1">{notif.desc}</div>
                                            </a>
                                        ))}
                                        {realNotifications.length === 0 && (
                                            <div className="p-3 text-center text-muted" style={{ fontSize: '11px' }}>{__('dashboard.no_notifications')}</div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Language Switcher */}
                            <div className="dropdown mr-2">
                                <div className="d-flex align-items-center justify-content-center pointer dropdown-toggle" id="langDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style={{ width: '34px', height: '34px', background: 'rgba(35, 16, 70, 0.6)', border: '1px solid rgba(168, 85, 247, 0.4)', borderRadius: '50%', fontSize: '11px', fontWeight: 800 }}>
                                    {authUser?.locale?.toUpperCase() ?? 'EN'}
                                </div>
                                <div className="dropdown-menu dropdown-menu-right" aria-labelledby="langDropdown" style={{ background: '#130924', border: '1px solid #8A4FFF', borderRadius: '10px', minWidth: '130px' }}>
                                    <a className="dropdown-item py-2" href="?lang=en" style={{ color: '#d8b4fe', fontSize: '12px' }}>EN &nbsp; {__('dashboard.lang_en')}</a>
                                    <a className="dropdown-item py-2" href="?lang=ar" style={{ color: '#d8b4fe', fontSize: '12px' }}>AR &nbsp; {__('dashboard.lang_ar')}</a>
                                </div>
                            </div>

                            {/* User Profile */}
                            <div className="dropdown">
                                <div className="user-data d-flex align-items-center px-2 py-1 dropdown-toggle pointer" id="userMenuDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style={{ background: 'rgba(35, 16, 70, 0.6)', border: '1px solid rgba(168, 85, 247, 0.4)', borderRadius: '20px' }}>
                                    <img src={authUser?.avatar_url ?? '/v8main/img/user.jpg'} alt="" className="avatar" style={{ width: '24px', height: '24px', borderRadius: '50%', objectFit: 'cover' }} />
                                    <span className="user-name d-none d-sm-inline ml-2 text-truncate" style={{ maxWidth: '90px', fontSize: '11px', fontWeight: 600 }}>{authUser?.name}</span>
                                </div>
                                <div className="dropdown-menu dropdown-menu-right" aria-labelledby="userMenuDropdown" style={{ background: '#130924', border: '1px solid #8A4FFF', borderRadius: '12px', minWidth: '170px' }}>
                                    {authUser?.roles?.[0] === 'admin' && (
                                        <Link className="dropdown-item py-2" href="/admin" style={{ color: '#d8b4fe', fontSize: '12px' }}><i className="icon-cog mr-2"></i>{__('dashboard.admin_panel')}</Link>
                                    )}
                                    <Link className="dropdown-item py-2" href="/profile" style={{ color: '#d8b4fe', fontSize: '12px' }}><i className="icon-user mr-2"></i>{__('dashboard.my_profile')}</Link>
                                    <div className="dropdown-divider" style={{ borderColor: 'rgba(138, 79, 255, 0.25)' }}></div>
                                    <Link className="dropdown-item py-2" href="/logout" method="post" as="button" style={{ color: '#f43f5e', fontSize: '12px', width: '100%', textAlign: 'left' }}><i className="icon-logout mr-2"></i>{__('dashboard.logout')}</Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            {/* Orbit Center Viewport */}
            <div className="orbital-viewport" id="orbitalViewport" data-core-glow={coreGlow}>
                {/* 3D core reactor core container */}
                <div className="orbital-center-core" id="orbitalCenter">
                    <div className="hud-dashboard-gauge">
                        <div className="hud-ring-outer"></div>
                        <div className="hud-ring-inner"></div>
                        <div className="hud-ticks"></div>
                    </div>

                    <div id="logo-it" ref={logoRef}></div>

                    <div className="orbital-identity">
                        <div className="c-name">{authUser?.name}</div>
                        <div className="c-role">{authUser?.roles?.[0] ? authUser.roles[0].replace('_', ' ').toUpperCase() : 'CLIENT'}</div>
                    </div>

                    <div className="dashboard-side-gauges">
                        <div className="side-gauge-left">
                            <span className="label">WALLET LEVEL</span>
                            <div className="gauge-bar-bg">
                                <div className="gauge-bar-fill emerald" data-target-width="85%" style={{ width: '0%' }}></div>
                            </div>
                            <span className="value text-emerald">{userBalanceFormatted}</span>
                        </div>
                        <div className="side-gauge-right">
                            <span className="label">DUES STATUS</span>
                            <div className="gauge-bar-bg">
                                <div className="gauge-bar-fill red" data-target-width={unpaidAmount > 0 ? '75%' : '0%'} style={{ width: '0%' }}></div>
                            </div>
                            <span className="value text-red">{totalDueFormatted}</span>
                        </div>
                    </div>
                </div>

                {/* Dashboard Node links stream */}
                <div className="orbital-arc-container">
                    {/* Left flank: General links */}
                    <div className="orbital-col orbital-left">
                        <div className="orbital-col-header">{__('dashboard.general_links')}</div>

                        <Link href="/financial/transactions" className="orbital-node" data-node="1" onMouseEnter={() => handleNodeHoverStart('emerald')} onMouseLeave={handleNodeHoverEnd} onClick={playClickSound}>
                            <div className="cube-3d-scene cube-theme-emerald">
                                <div className="cube-3d">
                                    <div className="cube-face cube-face-front">
                                        <i data-lucide="wallet"></i>
                                        <span className="cube-inner-label">{__('dashboard.wallet')}</span>
                                    </div>
                                    <div className="cube-face cube-face-back"></div>
                                    <div className="cube-face cube-face-right"></div>
                                    <div className="cube-face cube-face-left"></div>
                                    <div className="cube-face cube-face-top"></div>
                                    <div className="cube-face cube-face-bottom"></div>
                                </div>
                            </div>
                        </Link>

                        <Link href="/financial/add-balance" className="orbital-node" data-node="2" onMouseEnter={() => handleNodeHoverStart('emerald')} onMouseLeave={handleNodeHoverEnd} onClick={playClickSound}>
                            <div className="cube-3d-scene cube-theme-emerald">
                                <div className="cube-3d">
                                    <div className="cube-face cube-face-front">
                                        <i data-lucide="plus-circle"></i>
                                        <span className="cube-inner-label">{__('dashboard.add_balance')}</span>
                                    </div>
                                    <div className="cube-face cube-face-back"></div>
                                    <div className="cube-face cube-face-right"></div>
                                    <div className="cube-face cube-face-left"></div>
                                    <div className="cube-face cube-face-top"></div>
                                    <div className="cube-face cube-face-bottom"></div>
                                </div>
                            </div>
                        </Link>

                        <button className="orbital-node border-0 p-0 bg-transparent text-left" data-node="3" onMouseEnter={() => handleNodeHoverStart('red')} onMouseLeave={handleNodeHoverEnd} onClick={() => { playClickSound(); setIsPayDueOpen(true); }} style={{ outline: 'none', cursor: 'pointer' }}>
                            <div className="cube-3d-scene cube-theme-red">
                                <div className="cube-3d">
                                    <div className="cube-face cube-face-front">
                                        <i data-lucide="banknote"></i>
                                        <span className="cube-inner-label">{__('dashboard.pay_due_amount')}</span>
                                    </div>
                                    <div className="cube-face cube-face-back"></div>
                                    <div className="cube-face cube-face-right"></div>
                                    <div className="cube-face cube-face-left"></div>
                                    <div className="cube-face cube-face-top"></div>
                                    <div className="cube-face cube-face-bottom"></div>
                                </div>
                                {unpaidAmount > 0 && <span className="c-box-badge">{totalDueFormatted}</span>}
                            </div>
                        </button>

                        <Link href="/projects" className="orbital-node" data-node="4" onMouseEnter={() => handleNodeHoverStart('pink')} onMouseLeave={handleNodeHoverEnd} onClick={playClickSound}>
                            <div className="cube-3d-scene cube-theme-pink">
                                <div className="cube-3d">
                                    <div className="cube-face cube-face-front">
                                        <i data-lucide="folder"></i>
                                        <span className="cube-inner-label">{__('dashboard.my_projects_btn')}</span>
                                    </div>
                                    <div className="cube-face cube-face-back"></div>
                                    <div className="cube-face cube-face-right"></div>
                                    <div className="cube-face cube-face-left"></div>
                                    <div className="cube-face cube-face-top"></div>
                                    <div className="cube-face cube-face-bottom"></div>
                                </div>
                            </div>
                        </Link>

                        <Link href="/billing/invoices" className="orbital-node" data-node="14" onMouseEnter={() => handleNodeHoverStart('pink')} onMouseLeave={handleNodeHoverEnd} onClick={playClickSound}>
                            <div className="cube-3d-scene cube-theme-pink">
                                <div className="cube-3d">
                                    <div className="cube-face cube-face-front">
                                        <i data-lucide="receipt"></i>
                                        <span className="cube-inner-label">{__('dashboard.invoices')}</span>
                                    </div>
                                    <div className="cube-face cube-face-back"></div>
                                    <div className="cube-face cube-face-right"></div>
                                    <div className="cube-face cube-face-left"></div>
                                    <div className="cube-face cube-face-top"></div>
                                    <div className="cube-face cube-face-bottom"></div>
                                </div>
                            </div>
                        </Link>

                        <Link href="/financial/transactions" className="orbital-node" data-node="15" onMouseEnter={() => handleNodeHoverStart('emerald')} onMouseLeave={handleNodeHoverEnd} onClick={playClickSound}>
                            <div className="cube-3d-scene cube-theme-emerald">
                                <div className="cube-3d">
                                    <div className="cube-face cube-face-front">
                                        <i data-lucide="history"></i>
                                        <span className="cube-inner-label">{__('dashboard.transactions')}</span>
                                    </div>
                                    <div className="cube-face cube-face-back"></div>
                                    <div className="cube-face cube-face-right"></div>
                                    <div className="cube-face cube-face-left"></div>
                                    <div className="cube-face cube-face-top"></div>
                                    <div className="cube-face cube-face-bottom"></div>
                                </div>
                            </div>
                        </Link>

                        <Link href="/tickets" className="orbital-node" data-node="16" onMouseEnter={() => handleNodeHoverStart('amber')} onMouseLeave={handleNodeHoverEnd} onClick={playClickSound}>
                            <div className="cube-3d-scene cube-theme-amber">
                                <div className="cube-3d">
                                    <div className="cube-face cube-face-front">
                                        <i data-lucide="headset"></i>
                                        <span className="cube-inner-label">{__('dashboard.support')}</span>
                                    </div>
                                    <div className="cube-face cube-face-back"></div>
                                    <div className="cube-face cube-face-right"></div>
                                    <div className="cube-face cube-face-left"></div>
                                    <div className="cube-face cube-face-top"></div>
                                    <div className="cube-face cube-face-bottom"></div>
                                </div>
                            </div>
                        </Link>

                        <Link href="/referrals" className="orbital-node" data-node="17" onMouseEnter={() => handleNodeHoverStart('cyan')} onMouseLeave={handleNodeHoverEnd} onClick={playClickSound}>
                            <div className="cube-3d-scene cube-theme-cyan">
                                <div className="cube-3d">
                                    <div className="cube-face cube-face-front">
                                        <i data-lucide="users"></i>
                                        <span className="cube-inner-label">{__('dashboard.referrals')}</span>
                                    </div>
                                    <div className="cube-face cube-face-back"></div>
                                    <div className="cube-face cube-face-right"></div>
                                    <div className="cube-face cube-face-left"></div>
                                    <div className="cube-face cube-face-top"></div>
                                    <div className="cube-face cube-face-bottom"></div>
                                </div>
                            </div>
                        </Link>

                        <Link href="/financial/payout-methods" className="orbital-node" data-node="18" onMouseEnter={() => handleNodeHoverStart('emerald')} onMouseLeave={handleNodeHoverEnd} onClick={playClickSound}>
                            <div className="cube-3d-scene cube-theme-emerald">
                                <div className="cube-3d">
                                    <div className="cube-face cube-face-front">
                                        <i data-lucide="landmark"></i>
                                        <span className="cube-inner-label">{__('dashboard.payout_methods')}</span>
                                    </div>
                                    <div className="cube-face cube-face-back"></div>
                                    <div className="cube-face cube-face-right"></div>
                                    <div className="cube-face cube-face-left"></div>
                                    <div className="cube-face cube-face-top"></div>
                                    <div className="cube-face cube-face-bottom"></div>
                                </div>
                            </div>
                        </Link>

                        <Link href="/profile" className="orbital-node" data-node="20" onMouseEnter={() => handleNodeHoverStart('gold')} onMouseLeave={handleNodeHoverEnd} onClick={playClickSound}>
                            <div className="cube-3d-scene cube-theme-gold">
                                <div className="cube-3d">
                                    <div className="cube-face cube-face-front">
                                        <i data-lucide="user"></i>
                                        <span className="cube-inner-label">{__('dashboard.profile')}</span>
                                    </div>
                                    <div className="cube-face cube-face-back"></div>
                                    <div className="cube-face cube-face-right"></div>
                                    <div className="cube-face cube-face-left"></div>
                                    <div className="cube-face cube-face-top"></div>
                                    <div className="cube-face cube-face-bottom"></div>
                                </div>
                            </div>
                        </Link>
                    </div>

                    {/* Right flank: SaaS & Marketplace services */}
                    <div className="orbital-col orbital-right">
                        <div className="orbital-col-header">{__('dashboard.services')}</div>

                        <Link href="/isaas/contracts" className="orbital-node" data-node="5" onMouseEnter={() => handleNodeHoverStart('cyan')} onMouseLeave={handleNodeHoverEnd} onClick={playClickSound}>
                            <div className="cube-3d-scene cube-theme-cyan">
                                <div className="cube-3d">
                                    <div className="cube-face cube-face-front">
                                        <i data-lucide="file-signature"></i>
                                        <span className="cube-inner-label">{__('dashboard.contracts')}</span>
                                    </div>
                                    <div className="cube-face cube-face-back"></div>
                                    <div className="cube-face cube-face-right"></div>
                                    <div className="cube-face cube-face-left"></div>
                                    <div className="cube-face cube-face-top"></div>
                                    <div className="cube-face cube-face-bottom"></div>
                                </div>
                            </div>
                        </Link>

                        <Link href="/sso/erp" className="orbital-node" data-node="6" onMouseEnter={() => handleNodeHoverStart('cyan')} onMouseLeave={handleNodeHoverEnd} onClick={playClickSound}>
                            <div className="cube-3d-scene cube-theme-cyan">
                                <div className="cube-3d">
                                    <div className="cube-face cube-face-front">
                                        <i data-lucide="database"></i>
                                        <span className="cube-inner-label">{__('dashboard.erp_system')}</span>
                                    </div>
                                    <div className="cube-face cube-face-back"></div>
                                    <div className="cube-face cube-face-right"></div>
                                    <div className="cube-face cube-face-left"></div>
                                    <div className="cube-face cube-face-top"></div>
                                    <div className="cube-face cube-face-bottom"></div>
                                </div>
                            </div>
                        </Link>

                        <Link href="/sso/crm" className="orbital-node" data-node="7" onMouseEnter={() => handleNodeHoverStart('pink')} onMouseLeave={handleNodeHoverEnd} onClick={playClickSound}>
                            <div className="cube-3d-scene cube-theme-pink">
                                <div className="cube-3d">
                                    <div className="cube-face cube-face-front">
                                        <i data-lucide="contact"></i>
                                        <span className="cube-inner-label">{__('dashboard.crm_system')}</span>
                                    </div>
                                    <div className="cube-face cube-face-back"></div>
                                    <div className="cube-face cube-face-right"></div>
                                    <div className="cube-face cube-face-left"></div>
                                    <div className="cube-face cube-face-top"></div>
                                    <div className="cube-face cube-face-bottom"></div>
                                </div>
                            </div>
                        </Link>

                        <Link href="/sso/goldsaversys" className="orbital-node" data-node="8" onMouseEnter={() => handleNodeHoverStart('gold')} onMouseLeave={handleNodeHoverEnd} onClick={playClickSound}>
                            <div className="cube-3d-scene cube-theme-gold">
                                <div className="cube-3d">
                                    <div className="cube-face cube-face-front">
                                        <i data-lucide="coins"></i>
                                        <span className="cube-inner-label">{__('dashboard.gold_saver_sys')}</span>
                                    </div>
                                    <div className="cube-face cube-face-back"></div>
                                    <div className="cube-face cube-face-right"></div>
                                    <div className="cube-face cube-face-left"></div>
                                    <div className="cube-face cube-face-top"></div>
                                    <div className="cube-face cube-face-bottom"></div>
                                </div>
                            </div>
                        </Link>

                        <Link href="/sms-payment-gateway" className="orbital-node" data-node="9" onMouseEnter={() => handleNodeHoverStart('amber')} onMouseLeave={handleNodeHoverEnd} onClick={playClickSound}>
                            <div className="cube-3d-scene cube-theme-amber">
                                <div className="cube-3d">
                                    <div className="cube-face cube-face-front">
                                        <i data-lucide="smartphone"></i>
                                        <span className="cube-inner-label">{__('dashboard.sms_gateway')}</span>
                                    </div>
                                    <div className="cube-face cube-face-back"></div>
                                    <div className="cube-face cube-face-right"></div>
                                    <div className="cube-face cube-face-left"></div>
                                    <div className="cube-face cube-face-top"></div>
                                    <div className="cube-face cube-face-bottom"></div>
                                </div>
                            </div>
                        </Link>

                        <Link href="/sso/toolsys" className="orbital-node" data-node="10" onMouseEnter={() => handleNodeHoverStart('cyan')} onMouseLeave={handleNodeHoverEnd} onClick={playClickSound}>
                            <div className="cube-3d-scene cube-theme-cyan">
                                <div className="cube-3d">
                                    <div className="cube-face cube-face-front">
                                        <i data-lucide="terminal"></i>
                                        <span className="cube-inner-label">{__('dashboard.runtime_tools')}</span>
                                    </div>
                                    <div className="cube-face cube-face-back"></div>
                                    <div className="cube-face cube-face-right"></div>
                                    <div className="cube-face cube-face-left"></div>
                                    <div className="cube-face cube-face-top"></div>
                                    <div className="cube-face cube-face-bottom"></div>
                                </div>
                            </div>
                        </Link>

                        <Link href="/whatsapp-sender" className="orbital-node" data-node="11" onMouseEnter={() => handleNodeHoverStart('emerald')} onMouseLeave={handleNodeHoverEnd} onClick={playClickSound}>
                            <div className="cube-3d-scene cube-theme-emerald">
                                <div className="cube-3d">
                                    <div className="cube-face cube-face-front">
                                        <i data-lucide="message-square"></i>
                                        <span className="cube-inner-label">{__('dashboard.whatsapp')}</span>
                                    </div>
                                    <div className="cube-face cube-face-back"></div>
                                    <div className="cube-face cube-face-right"></div>
                                    <div className="cube-face cube-face-left"></div>
                                    <div className="cube-face cube-face-top"></div>
                                    <div className="cube-face cube-face-bottom"></div>
                                </div>
                            </div>
                        </Link>

                        <Link href="/fbmb" className="orbital-node" data-node="12" onMouseEnter={() => handleNodeHoverStart('pink')} onMouseLeave={handleNodeHoverEnd} onClick={playClickSound}>
                            <div className="cube-3d-scene cube-theme-pink">
                                <div className="cube-3d">
                                    <div className="cube-face cube-face-front">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="lucide lucide-custom-fb">
                                            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                                        </svg>
                                        <span className="cube-inner-label">{__('dashboard.fb_lookup')}</span>
                                    </div>
                                    <div className="cube-face cube-face-back"></div>
                                    <div className="cube-face cube-face-right"></div>
                                    <div className="cube-face cube-face-left"></div>
                                    <div className="cube-face cube-face-top"></div>
                                    <div className="cube-face cube-face-bottom"></div>
                                </div>
                            </div>
                        </Link>

                        <Link href="/marketplace/services" className="orbital-node" data-node="13" onMouseEnter={() => handleNodeHoverStart('amber')} onMouseLeave={handleNodeHoverEnd} onClick={playClickSound}>
                            <div className="cube-3d-scene cube-theme-amber">
                                <div className="cube-3d">
                                    <div className="cube-face cube-face-front">
                                        <i data-lucide="store"></i>
                                        <span className="cube-inner-label">{__('dashboard.browse_marketplace')}</span>
                                    </div>
                                    <div className="cube-face cube-face-back"></div>
                                    <div className="cube-face cube-face-right"></div>
                                    <div className="cube-face cube-face-left"></div>
                                    <div className="cube-face cube-face-top"></div>
                                    <div className="cube-face cube-face-bottom"></div>
                                </div>
                            </div>
                        </Link>

                        <Link href="/subscriptions/manage" className="orbital-node" data-node="19" onMouseEnter={() => handleNodeHoverStart('pink')} onMouseLeave={handleNodeHoverEnd} onClick={playClickSound}>
                            <div className="cube-3d-scene cube-theme-pink">
                                <div className="cube-3d">
                                    <div className="cube-face cube-face-front">
                                        <i data-lucide="shield-check"></i>
                                        <span className="cube-inner-label">{__('dashboard.subscriptions')}</span>
                                    </div>
                                    <div className="cube-face cube-face-back"></div>
                                    <div className="cube-face cube-face-right"></div>
                                    <div className="cube-face cube-face-left"></div>
                                    <div className="cube-face cube-face-top"></div>
                                    <div className="cube-face cube-face-bottom"></div>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>

            {/* Dues Payment Scifi Glass Modal */}
            {isPayDueOpen && (
                <div className="modal d-block modal-scifi-glass" style={{ background: 'rgba(14, 9, 32, 0.9)', zIndex: 1050 }}>
                    <div className="modal-dialog modal-dialog-centered modal-lg">
                        <div className="modal-content" style={{ background: 'rgba(14, 9, 32, 0.96)', backdropFilter: 'blur(25px)', border: '1.5px solid #00f0ff', borderRadius: '20px' }}>
                            <div className="modal-header d-flex align-items-center justify-content-between p-3" style={{ borderBottom: '1px solid rgba(0, 240, 255, 0.2)' }}>
                                <h4 className="modal-title font-weight-bold text-cyan">
                                    <i className="icon-basket mr-2"></i>{__('dashboard.modal_title')}
                                </h4>
                                <button type="button" onClick={() => setIsPayDueOpen(false)} className="close text-light bg-transparent border-0" style={{ outline: 'none' }}>
                                    <span style={{ fontSize: '28px', color: '#00f0ff' }}>&times;</span>
                                </button>
                            </div>
                            <div className="modal-body p-4 text-left">
                                <div className="p-3 mb-4 rounded d-flex align-items-center justify-content-between" style={{ background: 'rgba(244, 63, 94, 0.12)', border: '1px solid #f43f5e', borderRadius: '14px' }}>
                                    <div>
                                        <span className="text-uppercase text-muted d-block" style={{ fontSize: '11px' }}>{__('dashboard.total_due')}</span>
                                        <h2 className="m-0 font-weight-bold" style={{ color: '#f43f5e' }}>{totalDueFormatted}</h2>
                                    </div>
                                    <div className="text-right">
                                        <span className="text-uppercase text-muted d-block" style={{ fontSize: '11px' }}>{__('dashboard.wallet_balance')}</span>
                                        <h4 className="m-0 font-weight-bold text-cyan">{userBalanceFormatted}</h4>
                                    </div>
                                </div>

                                {userBalanceVal >= totalDueAmount && totalDueAmount > 0 ? (
                                    <div className="alert alert-success d-flex align-items-center mb-4" style={{ background: 'rgba(16, 185, 129, 0.15)', borderColor: '#10b981', color: '#10b981', borderRadius: '12px' }}>
                                        <i className="icon-check mr-2" style={{ fontSize: '20px' }}></i>
                                        <span>{__('dashboard.balance_covers')}</span>
                                    </div>
                                ) : totalDueAmount > 0 ? (
                                    <div className="alert alert-warning d-flex align-items-center mb-4" style={{ background: 'rgba(245, 158, 11, 0.15)', borderColor: '#f59e0b', color: '#f59e0b', borderRadius: '12px' }}>
                                        <i className="icon-attention mr-2" style={{ fontSize: '20px' }}></i>
                                        <span>{__('dashboard.balance_short')}</span>
                                    </div>
                                ) : null}

                                <h5 className="font-weight-bold text-light mb-3">{__('dashboard.outstanding_inv')}</h5>
                                <div className="table-responsive" style={{ maxHeight: '250px', overflowY: 'auto' }}>
                                    <table className="table table-hover table-dark mb-0" style={{ background: 'transparent' }}>
                                        <thead>
                                            <tr className="text-cyan">
                                                <th>{__('dashboard.invoice_num')}</th>
                                                <th>{__('dashboard.description')}</th>
                                                <th>{__('dashboard.status')}</th>
                                                <th>{__('dashboard.amount_due')}</th>
                                                <th>{__('dashboard.action')}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {unpaidInvoices.map((invoice, idx) => (
                                                <tr key={idx}>
                                                    <td>#{invoice.id}</td>
                                                    <td>{invoice.title ?? invoice.description ?? __('dashboard.service_invoice')}</td>
                                                    <td><span className="badge badge-warning">{__('dashboard.unpaid_badge')}</span></td>
                                                    <td className="font-weight-bold text-danger">{invoice.unpaid.toLocaleString(undefined, { minimumFractionDigits: 2 })} {currencySymbol}</td>
                                                    <td>
                                                        <Link href={`/billing/invoices/${invoice.id}`} className="btn btn-outline-info btn-sm" style={{ borderRadius: '8px' }}>{__('dashboard.pay_invoice')}</Link>
                                                    </td>
                                                </tr>
                                            ))}
                                            {unpaidInvoices.length === 0 && (
                                                <tr>
                                                    <td colSpan={5} className="text-center text-muted py-4">
                                                        <i className="icon-check d-block mb-2" style={{ fontSize: '30px', color: '#10b981' }}></i>
                                                        {__('dashboard.no_invoices')}
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div className="modal-footer d-flex align-items-center justify-content-between p-3" style={{ borderTop: '1px solid rgba(0, 240, 255, 0.2)' }}>
                                <Link href="/financial/add-balance" className="btn btn-outline-light btn-sm" style={{ borderRadius: '10px' }}>
                                    <i className="icon-plus mr-1"></i>{__('dashboard.add_balance')}
                                </Link>
                                <div>
                                    <button type="button" onClick={() => setIsPayDueOpen(false)} className="btn btn-secondary btn-sm mr-2" style={{ borderRadius: '10px' }}>{__('dashboard.cancel')}</button>
                                    <Link href="/billing/invoices" className="btn btn-primary btn-sm px-4" style={{ borderRadius: '10px', background: 'linear-gradient(135deg, #00f0ff, #d946ef)', border: 'none', fontWeight: 'bold' }}>
                                        {__('dashboard.view_all_invoices')}
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Universal Spotlight Search / Command Bar (Ctrl + K) Modal */}
            {isCommandBarOpen && (
                <div className="modal d-block modal-command-bar" style={{ background: 'rgba(13, 6, 26, 0.85)', zIndex: 1050 }}>
                    <div className="modal-dialog modal-dialog-centered modal-lg" style={{ maxWidth: '920px' }}>
                        <div className="modal-content" style={{ background: 'rgba(14, 9, 32, 0.98)', border: '1.5px solid #8A4FFF', borderRadius: '16px', boxShadow: '0 0 35px rgba(138, 79, 255, 0.4)' }}>
                            <div className="modal-body p-4 text-left">
                                <div className="d-flex align-items-center mb-3">
                                    <input type="text" value={commandQuery} onChange={(e) => setCommandQuery(e.target.value)} className="command-search-input w-100 p-2 text-light" style={{ background: 'rgba(35, 16, 70, 0.4)', border: '1px solid rgba(168, 85, 247, 0.4)', borderRadius: '10px', outline: 'none' }} placeholder="Search systems, tools, invoices, actions... (e.g. ERP, CRM, Gold, Wallet)" autoFocus />
                                </div>
                                <div className="command-results-container" style={{ maxHeight: '360px', overflowY: 'auto', overflowX: 'hidden' }}>
                                    {filteredSpotlight.map((item, index) => (
                                        <div key={index} onClick={() => { setIsCommandBarOpen(false); router.visit(item.href); }} className="command-result-item d-flex align-items-center justify-content-between p-3 mb-2 rounded pointer" style={{ background: 'rgba(13, 6, 26, 0.45)', border: '1px solid rgba(168, 85, 247, 0.4)', cursor: 'pointer' }}>
                                            <div>
                                                <i className={`${item.icon} text-cyan mr-2`}></i>
                                                <strong>{item.name}</strong>
                                                <span className="text-muted small ml-2">- {item.desc}</span>
                                            </div>
                                            <span className="badge badge-outline-info">{item.type}</span>
                                        </div>
                                    ))}
                                    {filteredSpotlight.length === 0 && (
                                        <div className="text-center text-muted py-4">No matching services found.</div>
                                    )}
                                </div>
                                <div className="d-flex align-items-center justify-content-between text-muted small mt-3 pt-2 border-top border-secondary">
                                    <span>Navigation: Click to open</span>
                                    <span>Close: <button onClick={() => setIsCommandBarOpen(false)} className="btn btn-link text-muted p-0 border-0 outline-none" style={{ textDecoration: 'none' }}><kbd className="command-bar-kbd">Esc</kbd></button></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
