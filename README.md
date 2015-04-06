BoilerGL
========

A place for beginners to WebGL.

This library is intended to make getting started a little easier. With BoilerGL you don't have to worry about the basics like a page load event or custom mouse interaction. It's all taken care of.

Simply install a copy of the repo, visit the home page and you're up and running with webGL!

There are three scripts embeded on the page. "1. USER MODIFICATIONS", "2. WINDOW LOADED" and "3. DEFAULTS". The first script is intended for you to modify. You have an "init" function where you do
initial setup and add objects to the scene. Then there's a loop function where you can adjust the objects you added. The second script is intended to fire after the window has loaded. Feel free to
add to this as well. The third script is the normal default loading. It takes care of initial setup and animation. Use caution when making changes to this script.

Add objects to the page by looking for the comment:

```
// ADD YOUR THINGS HERE
```

The ```init()``` and ```tween()``` methods are intended to be modified by your code. You can add objects in the init method and adjust the attributes of objects in the tween function.

Tips & Tricks
-------

Remember, you can't divide by zero. Best practice is instead of saying something like:

```javascript
// wrong
var stuff = 0;
```

Say something like this instead:

```javascript
var stuff = 0.0001;
```

To keep things fast. Group objects together. If you have a bunch of randomly placed objects, merge them to make one object:

```javascript
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
        0); // z

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
```
