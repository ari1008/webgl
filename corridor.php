<!DOCTYPE html>
<html lang="en">
<head>
    <title>three.js - pointerlock controls</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
    <link type="text/css" rel="stylesheet" href="main.css">
    <style>
        #blocker {
            position: absolute;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        #instructions {
            width: 100%;
            height: 100%;

            display: -webkit-box;
            display: -moz-box;
            display: box;

            -webkit-box-orient: horizontal;
            -moz-box-orient: horizontal;
            box-orient: horizontal;

            -webkit-box-pack: center;
            -moz-box-pack: center;
            box-pack: center;

            -webkit-box-align: center;
            -moz-box-align: center;
            box-align: center;

            color: #ffffff;
            text-align: center;
            font-family: Arial;
            font-size: 14px;
            line-height: 24px;

            cursor: pointer;
        }
    </style>
</head>
<body>
<div id="blocker">

    <div id="instructions">
        <span style="font-size:36px">Click to play</span>
        <br /><br />
        Move: WASD<br/>
        Jump: SPACE<br/>
        Look: MOUSE
    </div>

</div>

<script type="module">

    import * as THREE from '../build/three.module.js';
    import {GLTFLoader} from './jsm/loaders/GLTFLoader.js';
    import { PointerLockControls } from './jsm/controls/PointerLockControls.js';

    let camera, scene, renderer, controls;

    const objects = [];

    let raycaster;

    let moveForward = false;
    let moveBackward = false;
    let moveLeft = false;
    let moveRight = false;
    let canJump = false;

    let prevTime = performance.now();
    const velocity = new THREE.Vector3();
    const direction = new THREE.Vector3();
    const vertex = new THREE.Vector3();
    const color = new THREE.Color();

    init();
    animate();

    function init() {

        const manager = new THREE.LoadingManager();
        manager.onStart = function ( url, itemsLoaded, itemsTotal ) {

            console.log( 'Started loading file: ' + url + '.\nLoaded ' + itemsLoaded + ' of ' + itemsTotal + ' files.' );

        };

        manager.onLoad = function ( ) {

            console.log( 'Loading complete!');

        };


        manager.onProgress = function ( url, itemsLoaded, itemsTotal ) {

            console.log( 'Loading file: ' + url + '.\nLoaded ' + itemsLoaded + ' of ' + itemsTotal + ' files.' );

        };

        manager.onError = function ( url ) {

            console.log( 'There was an error loading ' + url );

        };

        // Instantiate a loader
        const loader = new GLTFLoader( manager );

// Load a glTF resource
        loader.load(
            // resource URL
            'corridor/scene.gltf',
            // called when the resource is loaded
            function ( gltf ) {
                var corridor = gltf.scene;
                scene.add( corridor );

                corridor.animations; // Array<THREE.AnimationClip>
                corridor.scene; // THREE.Group
                corridor.scenes; // Array<THREE.Group>
                corridor.cameras; // Array<THREE.Camera>
                corridor.asset; // Object
                var scale = 0.4;
                corridor.scale.set(scale,scale,scale);

            } );

        camera = new THREE.PerspectiveCamera( 75, window.innerWidth / window.innerHeight, 1, 1000 );
        camera.position.y = 10;

        scene = new THREE.Scene();
        scene.background = new THREE.Color( 0xffffff );
        scene.fog = new THREE.Fog( 0xffffff, 0, 750 );

        const light = new THREE.HemisphereLight( 0xeeeeff, 0x777788, 0.75 );
        light.position.set( 0.5, 1, 0.75 );
        scene.add( light );

        controls = new PointerLockControls( camera, document.body );

        const blocker = document.getElementById( 'blocker' );
        const instructions = document.getElementById( 'instructions' );

        instructions.addEventListener( 'click', function () {

            controls.lock();

        }, false );

        controls.addEventListener( 'lock', function () {

            instructions.style.display = 'none';
            blocker.style.display = 'none';

        } );

        controls.addEventListener( 'unlock', function () {

            blocker.style.display = 'block';
            instructions.style.display = '';

        } );

        scene.add( controls.getObject() );

        const onKeyDown = function ( event ) {

            switch ( event.keyCode ) {

                case 38: // up
                case 87: // w
                    moveForward = true;
                    break;

                case 37: // left
                case 65: // a
                    moveLeft = true;
                    break;

                case 40: // down
                case 83: // s
                    moveBackward = true;
                    break;

                case 39: // right
                case 68: // d
                    moveRight = true;
                    break;

                case 32: // space
                    if ( canJump === true ) velocity.y += 350;
                    canJump = false;
                    break;

            }

        };

        const onKeyUp = function ( event ) {

            switch ( event.keyCode ) {

                case 38: // up
                case 87: // w
                    moveForward = false;
                    break;

                case 37: // left
                case 65: // a
                    moveLeft = false;
                    break;

                case 40: // down
                case 83: // s
                    moveBackward = false;
                    break;

                case 39: // right
                case 68: // d
                    moveRight = false;
                    break;

            }

        };

        document.addEventListener( 'keydown', onKeyDown, false );
        document.addEventListener( 'keyup', onKeyUp, false );

        raycaster = new THREE.Raycaster( new THREE.Vector3(), new THREE.Vector3( 0, - 1, 0 ), 0, 10 );

        // floor

        let floorGeometry = new THREE.PlaneBufferGeometry( 2000, 2000, 100, 100 );
        floorGeometry.rotateX( - Math.PI / 2 );

        // vertex displacement

        let position = floorGeometry.attributes.position;



        floorGeometry = floorGeometry.toNonIndexed(); // ensure each face has unique vertices

        position = floorGeometry.attributes.position;



        const floorMaterial = new THREE.MeshBasicMaterial( { vertexColors: true } );

        const floor = new THREE.Mesh( floorGeometry, floorMaterial );
        scene.add( floor );

        //

        renderer = new THREE.WebGLRenderer( { antialias: true } );
        renderer.setPixelRatio( window.devicePixelRatio );
        renderer.setSize( window.innerWidth, window.innerHeight );
        document.body.appendChild( renderer.domElement );

        //

        window.addEventListener( 'resize', onWindowResize, false );

    }

    function onWindowResize() {

        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();

        renderer.setSize( window.innerWidth, window.innerHeight );

    }

    function animate() {

        requestAnimationFrame( animate );

        const time = performance.now();

        if ( controls.isLocked === true ) {

            raycaster.ray.origin.copy( controls.getObject().position );
            raycaster.ray.origin.y -= 10;

            const intersections = raycaster.intersectObjects( objects );

            const onObject = intersections.length > 0;

            const delta = ( time - prevTime ) / 1000;

            velocity.x -= velocity.x * 40.0 * delta;
            velocity.z -= velocity.z * 40.0 * delta;

            velocity.y -= 9.8 * 100.0 * delta; // 100.0 = mass

            direction.z = Number( moveForward ) - Number( moveBackward );
            direction.x = Number( moveRight ) - Number( moveLeft );
            direction.normalize(); // this ensures consistent movements in all directions

            if ( moveForward || moveBackward ) velocity.z -= direction.z * 400.0 * delta;
            if ( moveLeft || moveRight ) velocity.x -= direction.x * 400.0 * delta;

            if ( onObject === true ) {

                velocity.y = Math.max( 0, velocity.y );
                canJump = true;

            }

            controls.moveRight( - velocity.x * delta );
            controls.moveForward( - velocity.z * delta );

            controls.getObject().position.y += ( velocity.y * delta ); // new behavior

            if ( controls.getObject().position.y < 10 ) {

                velocity.y = 0;
                controls.getObject().position.y = 10;

                canJump = true;

            }

        }

        prevTime = time;

        renderer.render( scene, camera );

    }

</script>
</body>
</html>
