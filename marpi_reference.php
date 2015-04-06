<?php
$user = 'gsp';
if (isset($_GET['user'])) {
    $user = strtolower(htmlspecialchars($_GET['user']));
    $user = preg_replace("/[^A-Za-z0-9_]/", "", $user);
    if (strlen($user) > 30)
        $user = substr($user, 0, 30);
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>live</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
        <style>
            body {
                margin: 0px;
                overflow: hidden;
            }
        </style>
    </head>
    <body>
        <canvas id="canvasInstagram" width="1800" height="2100" style="display:none;"></canvas>

        <script src="js/three.js"></script>
        <script src="js/jquery-2.1.3.js"></script>
        <script src="js/controls/OrbitControls.js"></script>
        <script src="js/libs/stats.min.js"></script>
        <script src="js/libs/color-thief.min.js"></script>
        <script src="js/text.js"></script>
        <script src="js/TweenMax.min.js"></script>

        <script type="text/javascript" src="js/dat.gui.js"></script>
        <script type="text/javascript">
            var user = "<?php echo $user; ?>";
        </script>

        <script>
            var db = {};
            var currentInstagramImageDrawing = 0;
            var proxy = 'proxy/proxy.php/?path=';
            var API = "api/";
            $.getJSON(API, {
                user: user
            }).done(function (data) {
                data = data[0];

                console.log(data);
                if (!data) {
                    alert('no results');
                    return;
                }


                if (data.profile_banner_url)
                    db.bgImage = proxy + data.profile_banner_url;
                db.name = data.name;
                db.description = data.description;
                db.location = data.location;
                db.profile_image_url = proxy + data.profile_image_url.split("_normal").join("_bigger");
                db.followers_count = data.followers_count;
                db.friends_count = data.friends_count;

                var img = $('<img id="profile">');
                img.attr('src', proxy + data.profile_image_url);
                img.load(function () {
                    var colorThief = new ColorThief();
                    db.colors = colorThief.getPalette(img[0], 4);
                    var i = 0;
                    console.log(rgbToHex(db.colors[i][0], db.colors[i][1], db.colors[i][2]))

                    init();
                    animate();
                    
                    console.log('objects: ' + objects.length)
                    console.log('pyramids: ' + pyramids.length)
                    console.log('interactiveObjects: ' + interactiveObjects.length)
                    
                });
            });

            /*(function () {
             if(user=="gsp")user="goodby_silverstein";
             $.getJSON("proxy/instagram.php", {
             user: user
             }).done(function (data) {
             console.log(data);
             var canvas = document.getElementById('canvasInstagram');
             var context = canvas.getContext('2d');
             context.fillStyle = "#ffffff";
             context.fillRect(0, 0, 1800, 2100);
             for (var i = 0; i < 14 * 14; i++) {
             if(!data.data[i])continue;
             var imageObj = new Image();
             imageObj.onload = function () {
             var px = currentInstagramImageDrawing * 150;
             var py = 0;
             while (px >= 1800) {
             px -= 1800
             py += 150
             }
             if (imageObj)
             context.drawImage(this, px, py);
             if(instagramTexture)instagramTexture.needsUpdate=true;
             if (imageObj)
             currentInstagramImageDrawing++;
             }
             imageObj.src = proxy + data.data[i];
             
             };
             });
             
             })();*/
        </script>

        <script>

            var container, stats;
            var camera, controls, scene, renderer;
            var objects = [], plane;

            var raycaster = new THREE.Raycaster();
            var mouse = new THREE.Vector2(),
                    offset = new THREE.Vector3(),
                    INTERSECTED, SELECTED;

            var count = 2.2;
            var logo;
            var light;
            var dustParticles;
            var pyramids = []
            var interactiveObjects = []
            var textureCube;
            var controlling = false;
            var instagramTexture;

            function init() {

                container = document.createElement('div');
                document.body.appendChild(container);

                camera = new THREE.PerspectiveCamera(70, window.innerWidth / window.innerHeight, 1, 15000);
                scene = new THREE.Scene();
                scene.add(new THREE.AmbientLight(0x505050));
                scene.fog = new THREE.Fog(0xecece0, 1500, 10000);

                light = new THREE.DirectionalLight(0xFFFFFF, 1.5)
                light.position.set(0, 500, 2000);
                light.castShadow = true;
                light.shadowCameraVisible = true;

                light.shadowCameraNear = 200;
                light.shadowCameraFar = 4000;
                light.shadowCameraFov = 60;

                light.shadowBias = -0.00022;
                light.shadowDarkness = 0.5;

                light.shadowMapWidth = 2048;
                light.shadowMapHeight = 2048;

                scene.add(light);

                var r = "textures/cube/Bridge2/";
                var urls = [r + "px.jpg", r + "nx.jpg",
                    r + "py.jpg", r + "ny.jpg",
                    r + "pz.jpg", r + "nz.jpg"];

                textureCube = THREE.ImageUtils.loadTextureCube(urls /*, new THREE.CubeRefractionMapping()*/);
                textureCube.format = THREE.RGBFormat;

                plane = new THREE.Mesh(
                        new THREE.PlaneBufferGeometry(2000, 2000, 8, 8),
                        new THREE.MeshBasicMaterial({color: 0x000000, opacity: 0.25, transparent: true})
                        );
                plane.visible = false;
                scene.add(plane);

                renderer = new THREE.WebGLRenderer({antialias: true});
                renderer.setClearColor(0xdcdcd0);
                renderer.setPixelRatio(window.devicePixelRatio);
                renderer.setSize(window.innerWidth, window.innerHeight);
                renderer.sortObjects = false;

                //renderer.shadowMapEnabled = true//true;
                //renderer.shadowMapType = THREE.PCFShadowMap;

                container.appendChild(renderer.domElement);

                controls = new THREE.OrbitControls(camera, renderer.domElement);
                controls.rotateSpeed = 1.0;
                controls.zoomSpeed = 1.2;
                controls.panSpeed = 0.8;
                controls.noZoom = false;
                controls.noPan = false;
                controls.autoRotate = true;
                controls.autoRotateSpeed = Math.sin(Math.random() * Math.PI * 2) * .5;
                controls.staticMoving = true;
                controls.dynamicDampingFactor = 0.3;
                controls.minDistance = 200.0;
                controls.maxDistance = 2000.0;

                stats = new Stats();
                stats.domElement.style.position = 'absolute';
                stats.domElement.style.top = '0px';
                //container.appendChild( stats.domElement );

                renderer.domElement.addEventListener('mousemove', onDocumentMouseMove, false);
                renderer.domElement.addEventListener('mousedown', onDocumentMouseDown, false);
                renderer.domElement.addEventListener('mouseup', onDocumentMouseUp, false);

                window.addEventListener('resize', onWindowResize, false);

                while (camera.position.distanceTo(scene.position) < 500) {
                    camera.position.z = 1000 * (Math.random());
                    camera.position.y = 100 + 200 * (Math.random());
                    camera.position.x = 800 * (Math.random() - .5);
                }
                TweenMax.from(camera.position, 10, {delay: 0.1, x: 3000 * (Math.random() - .5), y: 2000 * (Math.random() - .5), z: 6000 * (Math.random() - .5), ease: Power1.easeInOut, onComplete: cameraAnimationDone});



                front();
                banner();
                bg();
                surroundings();
                cubes();
                glass();
                //instagram();
                carbon();
                shards();
                dust()
                
                
            }

            function cameraAnimationDone() {
                controlling = true;
            }

            function front() {
                var string = [db.location, db.name]//,db.description
                for (var j = 0; j < 2; j++) {
                    for (var i = 0; i < string.length; i++) {
                        var geometry = new Text(string[i].toUpperCase());
                        var material = new THREE.LineBasicMaterial({linecap: "square", linejoin: "miter", linewidth: 1 + j * 4, color: rgbToHex(db.colors[j][0], db.colors[j][1], db.colors[j][2]), opacity: 1, transparent: true});
                        var title = new THREE.Line(geometry, material, THREE.LinePieces);
                        title.position.x = 300 - (string[i].length * 3 * 4) / 2;
                        title.position.y = -130 + 30 * i;
                        title.position.z = 140 - j * 5;
                        title.scale.set(4 + 4 * i, 4 + 4 * i, 4 + 4 * i);
                        TweenMax.from(title.scale, .3, {delay: 2 - i / 2, x: 0.01, y: 0.01, z: 0.01});
                        scene.add(title);
                    }
                }

                var geometry = new THREE.BoxGeometry(80, 80, 80);
                var mat = new THREE.MeshLambertMaterial({map: THREE.ImageUtils.loadTexture(db.profile_image_url), reflectivity: .5, envMap: textureCube})
                var object = new THREE.Mesh(geometry, mat);
                object.position.x = 300;
                object.position.y = 0;
                object.position.z = 140;
                TweenMax.from(object.scale, 1, {delay: 3, x: 0.01, y: 0.01, z: 0.01, ease: Power1.easeOut});
                logo = object;
                scene.add(object)
                interactiveObjects.push(object);
            }

            function banner() {
                if (!db.bgImage)
                    return;
                var geometry = new THREE.PlaneGeometry(800, 300)
                var bannerMat = new THREE.MeshBasicMaterial({side: THREE.DoubleSide,
                    map: THREE.ImageUtils.loadTexture(db.bgImage/*"carbon_spec.jpg"*/)
                });

                var object = new THREE.Mesh(geometry, bannerMat);
                TweenMax.from(object.scale, 1, {delay: 4.5, x: 0.01, y: 0.01, z: 0.01, ease: Power1.easeOut});

                object.position.z = -500;
                scene.add(object);
                interactiveObjects.push(object);

            }

            function bg() {
                var bgMat = new THREE.MeshBasicMaterial({wireframe: true, side: THREE.BackSide});
                var object = new THREE.Mesh(new THREE.IcosahedronGeometry(2000, 1), bgMat);
                //TweenMax.from(object.scale, 5, {delay: 1, x: 13, y: 13, z: 13});
                //TweenMax.from(object.rotation, 3, {delay: 1, x: (Math.random()-.5)*3, y: (Math.random()-.5)*3, z: (Math.random()-.5)*3, ease: Power1.easeOut});

                scene.add(object)
            }

            function surroundings() {
                

                var mats = [];
                for (var i = 0; i < 3; i++) {
                    mats.push(new THREE.MeshLambertMaterial({shading: THREE.FlatShading,color: rgbToHex(db.colors[i][0], db.colors[i][1], db.colors[i][2]), reflectivity: 0, envMap: textureCube}))
                };

                for (var i = 0; i < 3; i++) {
                    var geometry = new THREE.Geometry();

                    for (var j = 0; j < 100; j++) {
                        var pregeom;
                        if(i%2==0) pregeom = new THREE.BoxGeometry(600, 600, 600);//
                        //if(i%2==1) pregeom = new THREE.IcosahedronGeometry(600, 1);
                        //if(i%4==2) pregeom = new THREE.OctahedronGeometry( 600, 0 );
                        if(i%2==1) pregeom = new THREE.TetrahedronGeometry(600, 0);
                        //change_uvs( pregeom, 1/zoom1, 1/zoom2, Math.floor(Math.random()*zoom1), Math.floor(Math.random()*zoom2) );
                        var submesh = new THREE.Mesh(pregeom);
                        while (submesh.position.distanceTo(scene.position) < 2000) {
                            submesh.position.x = (Math.random() - .5) * 20000;
                            submesh.position.y = (Math.random() - .5) * 20000;
                            submesh.position.z = (Math.random() - .5) * 20000;
                        }
                        submesh.updateMatrix()
                        geometry.merge(pregeom, submesh.matrix);
                    };
                
                    var object = new THREE.Mesh(geometry, mats[i]);
                    //TweenMax.from(object.scale, 1, {delay: 5 + i / 1000, x: 0.01, y: 0.01, z: 0.01, ease: Power1.easeOut});


                    object.castShadow = true;
                    object.receiveShadow = true;

                    scene.add(object);

                }
            }

            function cubes() {
                var geometry = new THREE.BoxGeometry(6, 6, 6);

                for (var j = 0; j < 7; j++) {
                    var pregeom = new THREE.BoxGeometry(6, 6, 6);
                    //change_uvs( pregeom, 1/zoom1, 1/zoom2, Math.floor(Math.random()*zoom1), Math.floor(Math.random()*zoom2) );
                    var submesh = new THREE.Mesh(pregeom);
                    submesh.position.x = (Math.random() - .5) * 125;
                    submesh.position.y = (Math.random() - .5) * 125;
                    submesh.position.z = (Math.random() - .5) * 125;
                    submesh.updateMatrix()
                    geometry.merge(pregeom, submesh.matrix);
                }
                ;

                var mats = [];
                for (var i = 0; i < 3; i++) {
                    mats.push(new THREE.MeshLambertMaterial({color: rgbToHex(db.colors[i][0], db.colors[i][1], db.colors[i][2]), reflectivity: 0, envMap: textureCube}))
                };

                for (var i = 0; i < 500 + 500 * db.followers_count / 2000000; i++) {

                    var object = new THREE.Mesh(geometry, mats[Math.floor(Math.random() * mats.length)]);

                    object.position.x = Math.random() * 400 - 200;
                    object.position.y = Math.random() * 200 - 100;
                    object.position.z = Math.random() * 300 - 150;

                    if (i % 3 == 0)
                        object.position.x -= Math.sin(i + count) * 2000
                    if (i % 3 == 1)
                        object.position.y -= Math.cos(i + count) * 2000
                    if (i % 3 == 2)
                        object.position.z -= Math.cos(3 * i + count) * 2000

                    //object.rotation.x = Math.random() * 2 * Math.PI;
                    //object.rotation.y = Math.random() * 2 * Math.PI;
                    //object.rotation.z = Math.random() * 2 * Math.PI;

                    object.scale.x = Math.random() * 1.5 + .5;
                    object.scale.y = Math.random() * 1.5 + .5;
                    object.scale.z = Math.random() * 1.5 + .5;


                    TweenMax.from(object.scale, 1, {delay: 5 + i / 1000, x: 0.01, y: 0.01, z: 0.01, ease: Power1.easeOut});


                    object.castShadow = true;
                    object.receiveShadow = true;

                    scene.add(object);

                    interactiveObjects.push(object);
                    objects.push(object);

                }
            }

            function glass() {

                var geometry = new THREE.Geometry()
                // glass
                //, shading: THREE.FlatShading

                var glassMat = new THREE.MeshPhongMaterial({
                    side: THREE.DoubleSide, blending: THREE.AdditiveBlending,
                    refractionRatio: 1, color: 0xffffff,
                    reflectivity: 1, envMap: textureCube,
                    transparent: true, opacity: 1});

                for (var i = 0; i < 300; i++) {

                    var object = new THREE.Mesh(new THREE.PlaneGeometry(20, 20), glassMat);

                    object.position.x = Math.random() * 1500 - 750;
                    object.position.y = Math.random() * 1500 - 750;
                    object.position.z = Math.random() * 1500 - 750;

                    //object.rotation.x = Math.random() * 2 * Math.PI;
                    //object.rotation.y = Math.random() * 2 * Math.PI;
                    //object.rotation.z = Math.random() * 2 * Math.PI;

                    object.lookAt(scene.position)

                    object.scale.x = Math.random() * 2 + .1;
                    object.scale.y = Math.random() * 2 + .1;
                    object.scale.z = Math.random() * 2 + .1;


                    object.updateMatrix()
                    geometry.merge(object.geometry, object.matrix);

                }
                var object = new THREE.Mesh(geometry, glassMat);

                scene.add(object);

                TweenMax.from(object.scale, 1, {delay: 2 + 3, x: 0.01, y: 0.01, z: 0.01, ease: Power1.easeOut});

            }

            function carbon() {

                var geometry = new THREE.Geometry()
                // glass
                //, shading: THREE.FlatShading
                
                //alert(rgbToHex(db.colors[2][0]/4, db.colors[2][1]/4, db.colors[2][2]/4))

                var glassMat = new THREE.MeshPhongMaterial({side: THREE.DoubleSide,
                    //shininess: 1,
                    refractionRatio: 1, color: rgbToHex(db.colors[1][0]/4, db.colors[1][1]/4, db.colors[1][2]/4),//0x333333,
                    //specularMap: THREE.ImageUtils.loadTexture("carbon_spec.jpg"),
                    normalMap: THREE.ImageUtils.loadTexture("carbon.jpg"),
                    //normalScale: new THREE.Vector2(1, 1)
                });

                for (var i = 0; i < 300; i++) {
                    var subgeometry = new THREE.PlaneGeometry(60, 60)

                    var object = new THREE.Mesh(subgeometry, glassMat);

                    object.position.x = Math.random() * 1500 - 750;
                    object.position.y = Math.random() * 600 - 300;
                    object.position.z = -450 + Math.random() * 400 - 200 - Math.cos(object.position.x / 500) * 400;

                    object.lookAt(scene.position)

                    object.rotation.x += (Math.random() - .5) * .14 * Math.PI;
                    object.rotation.y += (Math.random() - .5) * .14 * Math.PI;
                    object.rotation.z += (Math.random() - .5) * .14 * Math.PI;

                    object.scale.x = object.scale.y = object.scale.z = Math.random() * 2 + .1;

                    object.updateMatrix()
                    geometry.merge(object.geometry, object.matrix);

                }

                var object = new THREE.Mesh(geometry, glassMat);
                scene.add(object);
                TweenMax.from(object.scale, 1, {delay: 3.7, x: 0.01, y: 0.01, z: 0.01, ease: Power1.easeOut});

            }

            function instagram() {

                // glass
                //, shading: THREE.FlatShading

                /*var glassMat = new THREE.MeshPhongMaterial({side: THREE.DoubleSide,
                 shininess: 30,
                 refractionRatio: 1, color: 0x111111,
                 specularMap: THREE.ImageUtils.loadTexture("carbon_spec.jpg"),
                 normalMap: THREE.ImageUtils.loadTexture("carbon.jpg"),
                 normalScale: new THREE.Vector2(1, 1)});*/

                var zoom1 = 12;
                var zoom2 = 14;

                instagramTexture = new THREE.Texture(document.getElementById("canvasInstagram"));
                instagramTexture.needsUpdate = true;


                var geometry = new THREE.PlaneGeometry(60, 60)
                var glassMat = new THREE.MeshBasicMaterial({side: THREE.DoubleSide,
                    color: 0xffffff,
                    //shininess: 30, 
                    map: instagramTexture});


                for (var i = 0; i < 300; i++) {
                    var subgeometry = new THREE.PlaneGeometry(60, 60)

                    var object = new THREE.Mesh(subgeometry, glassMat);

                    object.position.x = Math.random() * 1500 - 750;
                    object.position.y = Math.random() * 600 - 300;
                    object.position.z = -250 + Math.random() * 400 - 200 - Math.cos(object.position.x / 500) * 400;

                    object.lookAt(camera.position)

                    object.rotation.x += (Math.random() - .5) * .4 * Math.PI;
                    object.rotation.y += (Math.random() - .5) * .4 * Math.PI;
                    object.rotation.z += (Math.random() - .5) * .4 * Math.PI;

                    var random = Math.floor(Math.random() * currentInstagramImageDrawing);

                    change_uvs(subgeometry, 1 / zoom1, 1 / zoom2, Math.floor(Math.random() * zoom1), Math.floor(Math.random() * zoom2));

                    object.scale.x = object.scale.y = object.scale.z = Math.random() * 2 + .1;



                    object.updateMatrix()
                    geometry.merge(object.geometry, object.matrix);


                    //interactiveObjects.push(object);

                }
                var object = new THREE.Mesh(geometry, glassMat);

                scene.add(object);

                TweenMax.from(object.scale, 1, {delay: 2 + 3, x: 0.01, y: 0.01, z: 0.01, ease: Power1.easeOut});

            }

            function shards() {

                //var geometry = new THREE.CubeGeometry( 4, 4, 4 );
                //var geometry = new THREE.IcosahedronGeometry( 10, 0 );
                //var geometry = new THREE.OctahedronGeometry( 10, 0 );
                var geometry = new THREE.Geometry();

                var mat = new THREE.MeshPhongMaterial({
                    ambient: 0x030303,
                    color: rgbToHex(db.colors[3][0], db.colors[3][1], db.colors[3][2]),
                    specular: 0x333333,
                    shininess: 10,
                    shading: THREE.FlatShading,
                    reflectivity: .3, envMap: textureCube,
                    opacity: 1,
                    side: THREE.DoubleSide,
                    transparent: true});
                for (var i = 0; i < 100 + 25 * db.friends_count / 10000; i++) {

                    var object = new THREE.Mesh(new THREE.TetrahedronGeometry(20, 0),
                            //new THREE.MeshLambertMaterial( { color: Math.random() * 0xffffff } )
                            //new THREE.MeshLambertMaterial( { color: 0xffffff } )
                            //new THREE.MeshPhongMaterial( { ambient: 0x050505, color: 0x000000, specular: 0x555555, shininess: 30 } ) 
                            mat
                            );

                    object.position.x = Math.random() * 800 - 400;
                    object.position.y = Math.random() * 800 - 400;
                    object.position.z = Math.random() * 800 - 400;

                    object.rotation.x = Math.random() * 2 * Math.PI;
                    object.rotation.y = Math.random() * 2 * Math.PI;
                    object.rotation.z = Math.random() * 2 * Math.PI;

                    object.scale.x = Math.random() + 0.5;
                    object.scale.y = Math.random() + 0.5;
                    object.scale.z = Math.random() + 0.5;

                    object.updateMatrix()
                    geometry.merge(object.geometry, object.matrix);

                }

                var object = new THREE.Mesh(geometry, mat);
                TweenMax.from(object.scale, 3, {delay: 2, x: 0.01, y: 0.01, z: 0.01, ease: Power1.easeOut});
                TweenMax.from(object.rotation, 3, {delay: 2, x: (Math.random() - .5) * 3, y: (Math.random() - .5) * 3, z: (Math.random() - .5) * 3, ease: Power1.easeOut});
                scene.add(object)

            }
            function dust() {


                // create the particle variables
                var particleCount = 1800,
                        particles = new THREE.Geometry(),
                        pMaterial = new THREE.PointCloudMaterial({
                            color: rgbToHex(db.colors[3][0], db.colors[3][1], db.colors[3][2]),
                            size: 10,
                            map: THREE.ImageUtils.loadTexture(
                                    "particle.png"
                                    ),
                            //blending: THREE.AdditiveBlending,
                            transparent: true
                        });

                // now create the individual particles
                for (var p = 0; p < particleCount; p++) {

                    // create a particle with random
                    // position values, -250 -> 250
                    var pX = Math.random() * 500 - 250,
                            pY = Math.random() * 500 - 250,
                            pZ = Math.random() * 500 - 250,
                            particle = new THREE.Vector3(pX * 6, pY * 6, pZ * 6)
                    // create a velocity vector
                    particle.velocity = new THREE.Vector3(
                            0, // x
                            -Math.random(), // y
                            0);				// z

                    // add it to the geometry
                    particles.vertices.push(particle);
                }

                // create the particle system
                var object = new THREE.PointCloud(
                        particles,
                        pMaterial);

                object.sortParticles = true;

                dustParticles = object;
                // add it to the scene
                scene.add(object);

                TweenMax.from(object.scale, 5, {delay: 1, x: 0, y: 0, z: 0, ease: Power1.easeOut});
                TweenMax.from(object.rotation, 5, {delay: 1, x: (Math.random() - .5) * 3, y: (Math.random() - .5) * 3, z: (Math.random() - .5) * 3, ease: Power1.easeOut});

            }

            function onWindowResize() {

                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();

                renderer.setSize(window.innerWidth, window.innerHeight);

            }

            function onDocumentMouseMove(event) {

                event.preventDefault();

                mouse.x = (event.clientX / window.innerWidth) * 2 - 1;
                mouse.y = -(event.clientY / window.innerHeight) * 2 + 1;

                //

                raycaster.setFromCamera(mouse, camera);

                if (SELECTED) {

                    var intersects = raycaster.intersectObject(plane);
                    if (!intersects[ 0 ])
                        return;
                    SELECTED.position.copy(intersects[ 0 ].point.sub(offset));
                    return;

                }

                var intersects = raycaster.intersectObjects(interactiveObjects);

                if (intersects.length > 0) {

                    if (INTERSECTED != intersects[ 0 ].object) {

                        if (INTERSECTED)
                            INTERSECTED.material.color.setHex(INTERSECTED.currentHex);

                        INTERSECTED = intersects[ 0 ].object;
                        INTERSECTED.currentHex = INTERSECTED.material.color.getHex();

                        plane.position.copy(INTERSECTED.position);
                        plane.lookAt(camera.position);

                    }

                    container.style.cursor = 'pointer';

                } else {

                    if (INTERSECTED)
                        INTERSECTED.material.color.setHex(INTERSECTED.currentHex);
                    INTERSECTED = null;
                    container.style.cursor = 'auto';

                }

            }

            function onDocumentMouseDown(event) {

                event.preventDefault();
                var vector = new THREE.Vector3(mouse.x, mouse.y, 0.5).unproject(camera);
                var raycaster = new THREE.Raycaster(camera.position, vector.sub(camera.position).normalize());
                var intersects = raycaster.intersectObjects(interactiveObjects);
                if (intersects.length > 0) {
                    controls.enabled = false;
                    SELECTED = intersects[ 0 ].object;
                    var intersects = raycaster.intersectObject(plane);
                    offset.copy(intersects[ 0 ].point).sub(plane.position);
                    container.style.cursor = 'move';
                }
            }

            function onDocumentMouseUp(event) {
                event.preventDefault();

                controls.enabled = true;

                if (INTERSECTED) {
                    plane.position.copy(INTERSECTED.position);
                    SELECTED = null;
                }

                container.style.cursor = 'auto';

            }

            //

            function animate() {

                requestAnimationFrame(animate);

                tween();
                render();
                stats.update();

            }

            function change_uvs(geometry, unitx, unity, offsetx, offsety) {

                var faceVertexUvs = geometry.faceVertexUvs[ 0 ];
                for (var i = 0; i < faceVertexUvs.length; i++) {
                    var uvs = faceVertexUvs[ i ];
                    for (var j = 0; j < uvs.length; j++) {
                        var uv = uvs[ j ];
                        uv.x = (uv.x + offsetx) * unitx;
                        uv.y = (uv.y + offsety) * unity;
                    }
                }
            }

            function tween() {
                count += .001;
                for (var i = 0; i < objects.length; i++) {
                    var o = objects[i]
                    if (i % 3 == 0)
                        o.position.x += Math.sin(i + count) / 2
                    if (i % 3 == 1)
                        o.position.y += Math.cos(i + count) / 2
                    if (i % 3 == 2)
                        o.position.z += Math.cos(3 * i + count) / 2
                }
                ;

                for (var i = 0; i < pyramids.length; i++) {
                    var o = pyramids[i]
                    o.rotation.x += Math.sin(i + count) / 100
                    o.rotation.y += Math.cos(i + count) / 100
                }
                logo.rotation.x += .005;
                logo.rotation.y += .005;
                dustParticles.rotation.x += .0001;
                dustParticles.rotation.y += .0001;
            }

            function render() {

                if (controlling) {
                    controls.update();
                } else {
                    camera.lookAt(scene.position);
                }
                renderer.render(scene, camera);
                light.position.x = camera.position.x
                light.position.y = camera.position.y
                light.position.z = camera.position.z
                controls.autoRotateSpeed = Math.sin(count * 2) * .5;

            }

            function componentToHex(c) {
                var hex = c.toString(16);
                return hex.length == 1 ? "0" + hex : hex;
            }

            function rgbToHex(r, g, b) {
                return "#" + componentToHex(Math.floor(r)) + componentToHex(Math.floor(g)) + componentToHex(Math.floor(b));
            }

        </script>

        <script type="text/javascript">
            var Gui = function () {
                this.twitter_account = user;
                this.speed = 0.8;
                this.displayOutline = false;
                this.gsp = function () {
                    window.location.href = '?user=gsp'
                };
                this.marmot = function () {
                    window.location.href = '?user=marmot'
                };
                this.amazon = function () {
                    window.location.href = '?user=amazon'
                };
                this.alibaba = function () {
                    window.location.href = '?user=alibabatalk'
                };
                this.target = function () {
                    window.location.href = '?user=target'
                };
                this.bestbuy = function () {
                    window.location.href = '?user=bestbuy'
                };
                this.salesforce = function () {
                    window.location.href = '?user=salesforce'
                };
                this.google = function () {
                    window.location.href = '?user=google'
                };
                this.microsoft = function () {
                    window.location.href = '?user=microsoft'
                };
                this.pepsi = function () {
                    window.location.href = '?user=pepsi'
                };
                this.ellie = function () {
                    window.location.href = '?user=mzelliesf'
                };
            };

            window.onload = function () {
                var text = new Gui();
                var gui = new dat.GUI();
                gui.add(text, 'twitter_account').name('@').onFinishChange(function (newValue) {
                    window.location.href = '?user=' + newValue
                });
                //console.log(gui.add(text, 'message'))
                //gui.add(text, 'speed', -5, 5);
                //gui.add(text, 'displayOutline');
                gui.add(text, 'gsp');
                gui.add(text, 'marmot');
                gui.add(text, 'amazon');
                gui.add(text, 'alibaba');
                gui.add(text, 'target');
                gui.add(text, 'bestbuy');
                gui.add(text, 'salesforce');
                gui.add(text, 'google');
                gui.add(text, 'microsoft');
                gui.add(text, 'pepsi');
                gui.add(text, 'ellie');
                gui.close();
            };
        </script>

    </body>
</html>
