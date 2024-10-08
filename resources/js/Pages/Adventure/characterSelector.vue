<template>
  <div class="character-selection">
    <div class="character-list">
      <div
        v-for="(story, index) in backstories"
        :key="index"
        class="character-item"
        :class="{ selected: selectedCharacter === index }"
        @click="selectCharacter(index)"
      >
        <!-- Character portrait goes here (if available) -->
        <img :src="getCharacterImage(story)" alt="Character portrait" />
      </div>
    </div>
    <div class="character-details" v-if="selectedCharacter !== null">
      <h2>Choose Character</h2>
      <div class="details-info">
        <p>Class: {{ backstories[selectedCharacter].name }}</p>
        <p>Age: 26 <!-- Assuming the age is static as seen in the image --></p>
        <p>Description: {{ backstories[selectedCharacter].description }}</p>
      </div>
    </div>
  </div>
</template>


<script setup>
import { ref } from 'vue';

const props = defineProps({
  backstories: Array,
});

const selectedCharacter = ref(null);

function selectCharacter(index) {
  selectedCharacter.value = index;
}

function getCharacterImage(character) {
  // Assuming you have character images based on their class name or id
  return `/images/characters/${character.id}.png`; // Or any logic to get the correct image
}
</script>

<style scoped>
@import '../../../css/characterSelector/selector.css'; /* Adjust the path as needed */
</style>
