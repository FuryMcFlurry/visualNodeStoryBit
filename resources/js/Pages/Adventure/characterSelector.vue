<template>
  <div class="character-selection">
    <div class="character-container">
      <div class="character-image" v-if="selectedCharacter !== null">
        <img
          class="large-character-image"
          :src="getCharacterImage(backstories[selectedCharacter])"
          alt="Selected Character"
        />
      </div>
      <div class="details-info">
        <h2>Choose Character</h2>
        <p>Class: {{ backstories[selectedCharacter].name }}</p>
        <p>Age: 26</p>
        <p>Description: {{ backstories[selectedCharacter].description }}</p>

        <div class="select-button" @click="showCharacterModal">
          <p>Select Character</p>
        </div>
      </div>
    </div>

    <div class="character-list">
      <div
        v-for="(story, index) in backstories"
        :key="index"
        class="character-item"
        :class="{ selected: selectedCharacter === index, 'taken': story.taken === story.id }"
        @click="story.taken === story.id ? null : selectCharacter(index)"
      >
        <img class="thumbnail" :src="getCharacterImage(story)" alt="Character thumbnail" />
      </div>
    </div>

    <!-- fades -->
    <transition name="fade">
      <div v-if="showOverlay" class="overlay"></div>
    </transition>

    <!-- enter-from initial enter-to when final enter-active is transitioning from 1 to another. -->

    <!-- modal -->
    <transition name="fade">
      <div v-if="showModal" class="modal">
        <div class="modal-content">
          <span class="close-button" @click="closeModal">&times;</span>
          <h2>Character Selection Confirmation</h2>
          <p>Are you sure you want to select {{ backstories[selectedCharacter].name }}?</p>
          <button @click="confirmSelection">Confirm</button>
          <button @click="closeModal">Cancel</button>
        </div>
      </div>
    </transition>


  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import axios from 'axios';

const props = defineProps({
  backstories: Array,
});

const selectedCharacter = ref(0);
const showOverlay = ref(false);
const showModal = ref(false);

onMounted(() => {
  const availableCharacterIndex = props.backstories.findIndex(story => story.taken === 0);
  if (availableCharacterIndex !== -1) {
    selectedCharacter.value = availableCharacterIndex;
  }
});

function selectCharacter(index) {
  selectedCharacter.value = index;
}

function getCharacterImage(character) {
  return `/images/characterSelector/${character.name}/full.png`;
}

function showCharacterModal() {
  showOverlay.value = true; // Show the overlay
  showModal.value = true;   // Show the modal
}

function closeModal() {
  showModal.value = false; // Close the modal
  showOverlay.value = false; // Close the overlay
}

function confirmSelection() {
  // Logic to handle character selection confirmation
  props.backstories[selectedCharacter.value].taken = props.backstories[selectedCharacter.value].id;
  axios.post('/character-select/confirm', {
    characterId: props.backstories[selectedCharacter.value].id,
  })
  .then(response => {
    // Handle success response
    console.log(response.data.message);
    closeModal(); // Close modal after confirmation
  })
  .catch(error => {
    // Handle error response
    console.error('There was an error confirming the selection:', error);
  });
  
  closeModal(); // Close modal after confirmation
}

</script>

<style scoped>
@import '../../../css/characterSelector/selector.css'; /* Adjust the path as needed */
</style>
