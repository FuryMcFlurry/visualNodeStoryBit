<template>
  <div class="background-container">
    
    <div class="text-container">
      <div class="text-wrapper">
        <p class="text-style" v-html="displayedText"></p>
      </div>
      <img v-if="!isTyping" class="next-page-indicator" src="/images/nextpageindicator.webp" alt="Next Page Indicator">
    </div>
    <div class="choice-container" v-show="displayChoices">
      <ul>
        <li v-for="(choice, index) in currentChoices" 
            :key="index" 
            :class="{ selected: index === selectedChoiceIndex }"
            @click="selectChoice(index)">
            <p class="text-style">{{ choice.text }}</p>
        </li>
      </ul>
    </div>
    <div class="options-container">
      <div class="options-button" @click="toggleOptionsMenu">
        ⚙️
      </div>
      <div class="options-menu" v-show="showOptionsMenu">
        <div class="volume-control">
          <label for="volume-slider">Sound Volume:</label>
          <input type="range" id="volume-slider" min="0" max="1" step="0.01" v-model="volume" @input="updateVolume">
        </div>
        <div class="volume-control">
          <label for="ambience-slider">Ambience:</label>
          <input type="range" id="ambience-slider" min="0" max="1" step="0.01" v-model="ambienceVolume" @input="updateAmbienceVolume">
        </div>
        <div class="volume-control">
          <label for="music-slider">Music:</label>
          <input type="range" id="music-slider" min="0" max="1" step="0.01" v-model="musicVolume" @input="updateMusicVolume">
        </div>
        <div class="volume-control">
          <button id="save" @click="saveProgress">Save</button>
        </div>
      </div>
    </div>
  </div>
</template>


<script setup>
import { ref, onMounted, watch } from 'vue'

const props = defineProps({
  graphData: Object
});
//menu states.
const showOptionsMenu = ref(false);

const displayedText = ref('');
let currentNodeId = ref(props.graphData.nodes[0].id);
let isTyping = ref(false);
let typingInterval = null;

//choices 
const displayChoices = ref(false);
const currentChoices = ref([]);
const selectedChoiceIndex = ref(0);


//Audio bits.
const volume = ref(getCookie('volume') ? parseFloat(getCookie('volume')) : 0);
const musicVolume = ref(getCookie('musicVolume') ? parseFloat(getCookie('musicVolume')) : 0);
const ambienceVolume = ref(getCookie('ambienceVolume') ? parseFloat(getCookie('ambienceVolume')) : 0);

const hasVisited = getCookie('hasVisited'); //Used to set defaults of volumes at first moments.

const typingSounds = [
  new Audio('/audio/sounds/typing1.ogg'),
  new Audio('/audio/sounds/typing2.ogg')
];
const dotSound = new Audio('/audio/sounds/typing3.ogg');

// Music audio setup
const musicAudio = new Audio('/audio/music/FH2Memories.ogg');  // Default placeholder music
musicAudio.loop = true;
musicAudio.volume = musicVolume.value;

// Ambience audio setup
const ambienceAudio = new Audio('/audio/music/trainloop.ogg');
ambienceAudio.loop = true;
ambienceAudio.volume = ambienceVolume.value;


//User data, holds onto backstory, traits and other important things.
const userData = ref(['dummyItem1', 'dummyItem2']); // Initialize with some dummy data



onMounted(() => {
  // Assume the start node is always the first node in the array
   // Fetch the start node using the findNodeByType function
  const startNode = findNodeByType('start');

  // Check if the start node exists
  if (startNode) {
    // Set the current node ID to the start node's ID
    currentNodeId.value = startNode.id;

    // Display the text from the start node
    displayText(startNode.data.text || "This is the start! via findNodeByType");
  } else {
    // Fallback: Use the first node in the array (optional)
    console.error("Start node not found! Using the first node as fallback.");
    currentNodeId.value = props.graphData.nodes[0].id;
    displayText(props.graphData.nodes[0].data.text || "This is the start!");
  }

  document.addEventListener('keydown', handleKeyPress);

  if (!hasVisited) { //does this cookie exist?
    setCookie('hasVisited', true, 2)
    volume.value = 1
    musicVolume.value = 1
    ambienceVolume.value = 0.7
    updateVolume();
    updateMusicVolume();
    updateAmbienceVolume();
  }
  

  document.addEventListener('click', startMusicPlayback, { once: true });
});

watch(() => props.graphData, () => {
  // Again, assume the start node is always the first node in the array
  currentNodeId.value = props.graphData.nodes[0].id;
  displayText(props.graphData.nodes[0].data.text || "This is the start!");
});


//nods are handled here,

function handleTextNode(node) {
  if (node.data.text) {
    displayText(node.data.text);
  }
}

function handleChoiceNode(node) {
  if (node.data.text) {
    displayText(node.data.text, () => {
      displayChoicesMenu(node.data.choices);
    });
  }
}

function handleRedirectNode(currentNode) {
    const redirects = currentNode.data.redirects || [];
    let redirectFound = false;
    
    redirects.forEach(redirect => {
        if (redirect && redirect.text && userData.value.includes(redirect.text)) {
            console.log(`Redirecting to ${redirect.text}`);
            // Handle redirect here
            redirectFound = true;
        }
    });

    if (!redirectFound) {
        console.log('Default or fallback action');
        // Handle default action here
    }
}

function handleKeyAdderNode(node) {
    // Extract the "keyAdd" field from the node data
    const keyToAdd = node.data.keyAdd;
    
    // Add the keyToAdd (e.g., "item:rustyKey") to the userData array
    if (keyToAdd) {
        userData.value.push(keyToAdd);
        console.log(`Added a new item: ${keyToAdd}`);
    } else {
        console.log('No keyAdd found in node data');
    }

    // After handling redirects, move to the next node.
    getNextNode();
    processCurrentNode();  // Continue processing with the next node.
}


function startMusicPlayback() {
  // Start playing the music once the user interacts with the page
  musicAudio.play().catch(error => {
    console.error('Failed to play music:', error);
  });
  ambienceAudio.play().catch(error => {
    console.error('Failed to play ambience:', error);
  });
}

function toggleOptionsMenu() {
  showOptionsMenu.value = !showOptionsMenu.value;
}

// Function to update volume
function updateVolume() {
  dotSound.volume = volume.value;
  typingSounds.forEach(sound => sound.volume = volume.value);
  

  // Save volume to cookie
  setCookie('volume', volume.value, 2);
}

function updateMusicVolume() {
  musicAudio.volume = musicVolume.value;

  // Save music volume to cookie
  setCookie('musicVolume', musicVolume.value, 2);
}

function updateAmbienceVolume() {
  ambienceAudio.volume = ambienceVolume.value;

  // Save music volume to cookie
  setCookie('ambienceVolume', ambienceVolume.value, 2); 
}

function playSound(character) {
  if (character === '.' || character === '!' || character === '?') {
    dotSound.playbackRate = 1.5;
    dotSound.volume = volume.value;
    dotSound.play();
  } else {
    const sound = typingSounds[Math.floor(Math.random() * typingSounds.length)];
    sound.playbackRate = 1;
    sound.volume = volume.value;
    sound.play();
  }
}

function displayText(text, callback) {
    
  if (isTyping.value) {
    clearInterval(typingInterval);
    displayedText.value = getCurrentNode().data.text;
    isTyping.value = false;
    playSound('.');
    
    const currentNode = getCurrentNode();
    if (currentNode && currentNode.type === 'choice' && currentNode.data.choices) {
      displayChoicesMenu(currentNode.data.choices);
    }
    return
  } 

  if (typingInterval) {
    clearInterval(typingInterval);
  }

  displayedText.value = '';
  let index = 0;
  isTyping.value = true;

  typingInterval = setInterval(() => {
    if (index < text.length) {
      const character = text[index];
      displayedText.value += character;
      playSound(character);
      index++;
    } else {
      clearInterval(typingInterval);
      isTyping.value = false;
      if (callback) callback(); // Call the callback function when done
    }
  }, 60); // Adjust the speed here (60ms per character)
}

function getCurrentNode() {
  return props.graphData.nodes.find(node => node.id === currentNodeId.value);
}

function getNextNode() {
  //lazy workaround to stop it from proceeding to next node if we're still typing.
  //maybe just add another && !isSkip() for when we implement skipping...
  //if (isTyping.value) { return; }
  const currentNode = getCurrentNode();
  console.log(currentNode.type);
  // Find the edge that starts from the current node
  const edge = props.graphData.edges.find(edge => edge.source === currentNodeId.value);
  
  // If an edge exists, update the current node ID to the target of that edge
  if (edge) {
    currentNodeId.value = edge.target;
  }
}

function displayChoicesMenu(choices) {
  // Update choices with dynamically determined target node IDs
  const updatedChoices = choices.map((choice, index) => {
    const targetNodeId = findTargetNodeId(currentNodeId.value, index);
    return {
      ...choice,
      target: targetNodeId
    };
  });

  currentChoices.value = updatedChoices;
  displayChoices.value = true;
  selectedChoiceIndex.value = 0; // Reset selection to the first choice
}

function findTargetNodeId(currentNodeId, choiceIndex) {
  // Find the edge that starts from the current node and matches the choice index
  const edge = props.graphData.edges.find(edge =>
    edge.source === currentNodeId && edge.sourceHandle === `output-${choiceIndex}`
  );
  
  // Return the target node ID if found
  return edge ? edge.target : null;
}

function findNodeByType(type) {
  // Search for the first node that matches the specified type
  const node = props.graphData.nodes.find(node => node.type === type);

  // Return the node if found, or null if not found
  return node || null;
}

function handleChoiceSelection() {
  const selectedChoice = currentChoices.value[selectedChoiceIndex.value];
  console.log('Selected choice:', selectedChoice);
  displayChoices.value = false

  // Use the target node ID from the updated choice
  const targetNodeId = selectedChoice.target;

  if (targetNodeId) {
    console.log('Moving to target node:', targetNodeId);
    currentNodeId.value = targetNodeId;
    displayChoices.value = false; // Hide the choices

    const nextNode = getCurrentNode();
    console.log('Next node:', nextNode);

    // if (nextNode && nextNode.data && nextNode.data.text) {
    //   displayText(nextNode.data.text);
    // }
  } else {
    console.error('No target node ID found in the selected choice.');
  }
  processCurrentNode();
}

// Function to change the music track
function changeMusic(trackPath) {
  musicAudio.src = trackPath;
  musicAudio.load();  // Load the new track
  musicAudio.play();  // Start playing the new track
  musicAudio.volume = musicVolume.value;  // Apply current volume settings
}

function saveProgress() {
  const progress = {
    nodeId: currentNodeId.value,
    volume: volume.value,
    musicVolume: musicVolume.value,
    ambienceVolume: ambienceVolume.value
  };

  fetch('/save-progress', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    },
    body: JSON.stringify({ progress })
  }).then(response => response.json())
    .then(data => {
      console.log('Progress saved:', data);
    }).catch(error => {
      console.error('Error saving progress:', error);
    });
}

function loadProgress() {
  fetch('/load-progress', {
    method: 'GET',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    }
  }).then(response => response.json())
    .then(data => {
      const progress = data.progress;
      if (progress) {
        currentNodeId.value = progress.nodeId;
        volume.value = progress.volume;
        musicVolume.value = progress.musicVolume;
        ambienceVolume.value = progress.ambienceVolume;

        updateVolume();
        updateMusicVolume();
        updateAmbienceVolume();

        const currentNode = getCurrentNode();
        if (currentNode && currentNode.data && currentNode.data.text) {
          displayText(currentNode.data.text);
        }
      }
    }).catch(error => {
      console.error('Error loading progress:', error);
    });
}

function processCurrentNode() {
  const currentNode = getCurrentNode();
  isTyping.value = false;
  if (!currentNode) {
    console.error('No current node found.');
    return;
  }

  switch (currentNode.type) {
    case 'textInput':
      handleTextNode(currentNode);
      getNextNode();
      break;

    case 'start':
      handleTextNode(currentNode);  // Ensure this is correct if the 'start' node contains text
      getNextNode();
      break;

    case 'choice':
      handleChoiceNode(currentNode);
      break;

    case 'redirect':
      handleRedirectNode(currentNode);
      getNextNode();
      break;

    case 'keyAdder':
      handleKeyAdderNode(currentNode);
      getNextNode();
      break;
      
    case 'stop':
      // Handle the end of the path or sequence
      console.log("End of path.");
      break;
    default:
      console.error(`Unhandled node type: ${currentNode.type}`);
  }
  
}

function handleKeyPress(event) {
  if (displayChoices.value) {
    if (event.key === 'ArrowUp') {
      selectedChoiceIndex.value = (selectedChoiceIndex.value - 1 + currentChoices.value.length) % currentChoices.value.length;
    } else if (event.key === 'ArrowDown') {
      selectedChoiceIndex.value = (selectedChoiceIndex.value + 1) % currentChoices.value.length;
    } else if (event.key === 'Enter' || event.key === 'z') {
      handleChoiceSelection();
    }
  } else {
    if (event.key === 'Enter' || event.key === 'z') {
        processCurrentNode();
    }
  }
}

function typeHandler(){
  // Move to the next node
  getNextNode();
  processCurrentNode();
  // const currentNode = getCurrentNode();

  // if (currentNode) {
  //   if (currentNode.type === 'choice' && currentNode.data.choices) {
  //     // Display text for choice nodes, then show choices
  //     displayText(currentNode.data.text, () => {
  //       displayChoicesMenu(currentNode.data.choices);
  //     });
  //   } else if (currentNode.data.text) {
  //     // Display text for non-choice nodes
  //     displayText(currentNode.data.text);
  //   }
  // }
}



function selectChoice(index) {
  console.log('selectChoice : ' )
  console.log(index)
  displayChoices.value = false
  selectedChoiceIndex.value = index;
  handleChoiceSelection();
}

function setCookie(name, value, days) {
  let expires = "";
  if (days) {
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    expires = "; expires=" + date.toUTCString();
  }
  document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

function getCookie(name) {
  const nameEQ = name + "=";
  const ca = document.cookie.split(';');
  for(let i = 0; i < ca.length; i++) {
    let c = ca[i];
    while (c.charAt(0) === ' ') c = c.substring(1, c.length);
    if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
  }
  return null;
}


</script>

<style scoped>
.background-container {
  position: relative;
  width: 100vw;
  height: 100vh;
  overflow: hidden; /* Ensure no overflow issues */
}

.background-container::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-image: url('/images/ebx1g478fne11.jpg');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  filter: grayscale(50%);
  z-index: -1; /* Place it behind the other content */
}

.text-container, .choice-container, .options-button, .options-menu {
  position: relative; /* Ensure these elements are above the background */
}

.text-container {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 200px; /* Set a fixed height */
  padding: 20px;
  background-image: url('/images/nine_patch.png');
  background-size: 100% 100%;
  background-position: center;
  background-repeat: no-repeat;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  justify-content: space-between; /* Ensure content is distributed between the top and bottom */
}

.text-wrapper {
  flex-grow: 1;
  overflow-y: auto; /* Allows text to scroll if it overflows */
}

.next-page-indicator {
  image-rendering: crisp-edges;
  width: 48px; /* Adjust size as necessary */
  height: auto; /* Maintain aspect ratio */
  align-self: center; /* Centers the indicator horizontally */
}

.text-container::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 100%;
  background: rgba(0, 0, 0, 0.6);
  mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, 0.6) 5%, rgba(0, 0, 0, 0.6) 95%, rgba(0, 0, 0, 0) 100%);
  z-index: -1;
}

.text-style {
  font-family: 'CrimsonText-Regular', serif;
  color: #FFFFFF;
  text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.8), 2px 2px 2px rgba(0, 0, 0, 0.8), -1px -1px 1px rgba(0, 0, 0, 0.5), 1px -1px 1px rgba(0, 0, 0, 0.5);
  text-align: left;
  line-height: 1.5;
  font-size: 33px;
  background: transparent;
}

.choice-container {
  position: absolute;
  background-image: url('/images/nine_patch.png');
  background-size: 100% 100%;
  background-position: center;
  background-repeat: no-repeat;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  padding: 20px;
  border-radius: 10px;
  text-align: center;
}

.choice-container ul {
  list-style: none;
  padding: 0;
}

.choice-container li {
  padding: 10px;
  cursor: pointer;
  color: white;
  font-size: 20px;
  border-radius: 5px;
}

.choice-container li.selected {
  background-image: url('/images/selection.png');
  background-size: 100% 100%;
  background-position: center;
  background-repeat: no-repeat;
}

.options-container {
  position: absolute;
  top: 20px; /* Distance from the top of the container */
  right: 20px; /* Distance from the right of the container */
}

.options-button {
  cursor: pointer;
  background-color: rgba(0, 0, 0, 0.1);
  padding: 10px;
  border-radius: 50%;
  color: white;
  font-size: 20px;
}

.options-menu {
  display: flex;
  flex-direction: column;
  gap: 10px; /* Space between sliders */
  background-color: rgba(0, 0, 0, 0.1);
  padding: 20px;
  border-radius: 10px;
  position: absolute;
  top: 100%; /* Position it directly below the button */
  left: -66px; /* Align it with the left edge of the button */
  transform: translateY(10px); /* Add some space between the button and the menu */
  z-index: 10; /* Ensure the menu appears above other content */
}

.volume-control {
  display: flex;
  flex-direction: column;
}

.volume-control label {
  color: #fff;
  margin-bottom: 5px;
}

.volume-control input[type="range"] {
  width: 100%;
}
</style>
