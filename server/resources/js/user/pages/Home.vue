<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useInterface } from '../../shared/interface';

const { t } = useInterface();

defineProps<{
  announcements: { id: string; title: string; body: string }[];
  featuredTemplates?: {
    id: string;
    title: string;
    kind: string;
    category: string;
    cover_url: string | null;
  }[];
}>();
const ideas = [
  {
    name: 'Light & form',
    label: 'Create images',
    style: 'luminous',
    href: '/studio?kind=text_to_image',
  },
  {
    name: 'A little movement',
    label: 'Create videos',
    style: 'motion',
    href: '/studio?kind=image_to_video',
  },
  {
    name: 'Another perspective',
    label: '图片编辑',
    style: 'perspective',
    href: '/studio?kind=image_to_image',
  },
  {
    name: 'Your next idea',
    label: '模板库',
    style: 'dream',
    href: '/templates',
  },
];
</script>
<template>
  <Head :title="t('WeaveAI · Image & video studio')" />
  <div v-if="announcements.length" class="home-notices">
    <details v-for="item in announcements" :key="item.id">
      <summary>{{ item.title }}</summary>
      <p>{{ item.body }}</p>
    </details>
  </div>
  <section class="home-hero">
    <div class="hero-copy">
      <p class="eyebrow"><span />{{ t('Your next idea starts here.') }}</p>
      <h1>
        {{ t('Create an image.') }}<br /><span>{{
          t('Bring it to life.')
        }}</span>
      </h1>
      <p class="hero-description">
        {{
          t(
            'Start with a photo, a prompt, or a spark of inspiration. Your creative space for images and motion.',
          )
        }}
      </p>
      <div class="hero-actions">
        <Link href="/studio" class="primary-action"
          >{{ t('开始创作') }}<span aria-hidden="true">↗</span></Link
        ><Link href="/templates" class="secondary-action"
          >{{ t('Browse templates') }}<span aria-hidden="true">→</span></Link
        >
      </div>
      <p class="hero-note">
        {{ t('Recorded requests only. No generation or charges yet.') }}
      </p>
    </div>
    <div class="hero-previews">
      <Link href="/studio?kind=image_to_video" class="preview-art hero-video"
        ><span class="art-orbit" /><span class="art-glow" /><span
          class="art-pill"
          >{{ t('Create videos') }} ↗</span
        ><span class="play-mark" aria-hidden="true">▷</span></Link
      ><Link href="/studio?kind=text_to_image" class="preview-art hero-image"
        ><span class="art-sphere" /><span class="art-pill"
          >{{ t('Create images') }} ↗</span
        ></Link
      ><span class="preview-label">{{
        t('A space for every kind of imagination.')
      }}</span>
    </div>
  </section>
  <section class="home-discover">
    <div class="section-heading">
      <div>
        <p class="eyebrow">{{ t('发现') }}</p>
        <h2>{{ t('Find your next creative direction') }}</h2>
      </div>
      <Link href="/templates" class="view-all"
        >{{ t('View all templates') }} ↗</Link
      >
    </div>
    <nav class="discovery-tabs" :aria-label="t('创作类型')">
      <Link href="/templates" class="selected">{{ t('All') }}</Link
      ><Link href="/templates?kind=text_to_image">{{ t('Create images') }}</Link
      ><Link href="/templates?kind=image_to_video">{{
        t('Create videos')
      }}</Link
      ><Link href="/templates?kind=image_to_image">{{ t('图片编辑') }}</Link>
    </nav>
    <div v-if="featuredTemplates?.length" class="inspiration-grid">
      <Link
        v-for="item in featuredTemplates"
        :key="item.id"
        :href="`/templates/${item.id}`"
        class="inspiration-card"
        ><div class="inspiration-image">
          <img
            v-if="item.cover_url"
            :src="item.cover_url"
            :alt="item.title"
            loading="lazy"
          />
          <div v-else class="preview-art dream">
            <span class="art-sphere" />
          </div>
          <span class="card-arrow" aria-hidden="true">↗</span>
        </div>
        <p>{{ item.title }}</p>
        <span>{{ item.category }}</span></Link
      >
    </div>
    <div v-else class="inspiration-grid">
      <Link
        v-for="item in ideas"
        :key="item.name"
        :href="item.href"
        class="inspiration-card"
        ><div class="inspiration-image preview-art" :class="item.style">
          <span class="art-sphere" /><span class="art-orbit" /><span
            class="card-arrow"
            aria-hidden="true"
            >↗</span
          >
        </div>
        <p>{{ t(item.name) }}</p>
        <span>{{ t(item.label) }}</span></Link
      >
    </div>
  </section>
</template>
<style scoped>
.home-hero {
  display: grid;
  grid-template-columns: 1.05fr 1fr;
  gap: 35px;
  align-items: center;
  min-height: 440px;
  padding: 22px 0 54px;
}
.eyebrow {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--muted);
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 1.5px;
}
.eyebrow span {
  width: 5px;
  height: 5px;
  background: var(--accent);
  border-radius: 50%;
}
.hero-copy h1 {
  font-size: clamp(35px, 3.6vw, 62px);
  font-weight: 650;
  line-height: 1.12;
  letter-spacing: -2.5px;
  margin: 22px 0;
}
.hero-copy h1 span {
  color: var(--accent);
}
.hero-description {
  max-width: 390px;
  font-size: 14px;
  line-height: 1.9;
  color: var(--muted);
}
.hero-actions {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 24px;
  margin-top: 28px;
}
.primary-action {
  display: flex;
  align-items: center;
  gap: 30px;
  padding: 13px 22px;
  background: var(--accent);
  color: var(--accent-ink);
  border-radius: 12px;
  font-size: 13px;
  font-weight: 700;
}
.secondary-action {
  font-size: 12px;
  display: flex;
  gap: 12px;
  align-items: center;
}
.hero-note {
  font-size: 10px;
  color: var(--muted);
  line-height: 1.6;
  margin-top: 18px;
  max-width: 350px;
}
.hero-previews {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 15px;
  min-height: 350px;
}
.preview-art {
  position: relative;
  overflow: hidden;
  background: #26254a;
}
.hero-video {
  width: 57%;
  height: 315px;
  border-radius: 22px;
  transform: rotate(-5deg);
  background: radial-gradient(
    ellipse at 25% 25%,
    #b4ace1 0%,
    #786b9c 25%,
    #353c5f 52%,
    #172835 80%
  );
}
.hero-image {
  width: 39%;
  height: 255px;
  border-radius: 20px;
  transform: rotate(7deg);
  background: radial-gradient(
    ellipse at 70% 30%,
    #edcb92,
    #bf8662 40%,
    #6c4f59 75%
  );
}
.art-sphere {
  position: absolute;
  inset: 18% 18% 15%;
  border-radius: 48% 52% 54% 46%;
  background:
    linear-gradient(140deg, #ffffffb0, transparent 45%),
    radial-gradient(circle at 30% 25%, #c6debf, #627e6c 48%, #283534 80%);
  box-shadow: 15px 25px 40px #0005;
  transform: rotate(-16deg);
}
.hero-image .art-sphere {
  inset: 20% 13% 20%;
  background: linear-gradient(
    145deg,
    #f4e1b8,
    #e4ad8e 42%,
    #af6c69 70%,
    #684559
  );
  border-radius: 48% 52% 30% 70%;
}
.art-orbit {
  position: absolute;
  inset: 24% -20% 12%;
  border: 25px solid #bfc7ef6b;
  border-radius: 50%;
  transform: rotate(-40deg);
  box-shadow: 0 0 30px #d1c4e622;
}
.art-glow {
  position: absolute;
  width: 100px;
  height: 100px;
  top: 18%;
  left: 35%;
  background: #e8d6b3;
  border-radius: 50%;
  box-shadow: 0 0 65px #e5c3e3;
}
.art-pill {
  position: absolute;
  bottom: 16px;
  left: 14px;
  right: 14px;
  display: flex;
  justify-content: space-between;
  backdrop-filter: blur(20px);
  background: #1118;
  color: #fff;
  padding: 10px 12px;
  border-radius: 9px;
  font-size: 11px;
}
.play-mark {
  position: absolute;
  inset: 45% auto auto 43%;
  display: grid;
  place-items: center;
  border: 1px solid #fff8;
  color: #fff;
  width: 42px;
  height: 42px;
  border-radius: 50%;
  font-size: 22px;
  backdrop-filter: blur(8px);
}
.preview-label {
  position: absolute;
  bottom: -12px;
  color: var(--muted);
  font-size: 10px;
}
.section-heading {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: 20px;
}
.section-heading h2 {
  font-size: 24px;
  letter-spacing: -0.6px;
  font-weight: 600;
  margin-top: 10px;
}
.view-all {
  font-size: 11px;
  color: var(--muted);
  white-space: nowrap;
}
.discovery-tabs {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin: 24px 0;
}
.discovery-tabs a {
  padding: 8px 16px;
  border-radius: 8px;
  color: var(--muted);
  font-size: 12px;
}
.discovery-tabs a.selected {
  background: var(--elevated);
  color: var(--text);
}
.inspiration-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 18px;
}
.inspiration-image {
  position: relative;
  aspect-ratio: 4/5;
  border-radius: 16px;
  overflow: hidden;
  background: var(--surface);
}
.inspiration-image > img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s;
}
.inspiration-card:hover img {
  transform: scale(1.04);
}
.inspiration-card > p {
  font-size: 13px;
  font-weight: 500;
  margin: 13px 0 5px;
}
.inspiration-card > span {
  font-size: 10px;
  color: var(--muted);
}
.card-arrow {
  position: absolute;
  bottom: 12px;
  right: 12px;
  width: 28px;
  height: 28px;
  border: 1px solid #fff5;
  background: #0004;
  backdrop-filter: blur(8px);
  display: grid;
  place-items: center;
  border-radius: 50%;
  color: #fff;
}
.luminous {
  background: linear-gradient(155deg, #bfc4ac, #6d8178 40%, #35434e);
}
.motion {
  background: linear-gradient(135deg, #e6c49a, #a17673, #3b3a5c);
}
.motion .art-sphere {
  background: linear-gradient(120deg, #f2e9c0, #aa7c87, #664e72);
  border-radius: 30% 70% 64% 36%;
  transform: rotate(25deg);
}
.perspective {
  background: linear-gradient(135deg, #919dc4, #4c6080, #233c50);
}
.perspective .art-sphere {
  background: linear-gradient(140deg, #e1e5f0, #a4b7d1 45%, #4f6592);
  border-radius: 20% 70% 30% 50%;
  transform: rotate(40deg);
}
.dream {
  background: linear-gradient(145deg, #d2b5b7, #89738c 50%, #43425c);
}
.dream .art-sphere {
  background: linear-gradient(130deg, #f4d4c4, #c892aa, #66568b);
}
.home-notices details {
  border: 1px solid var(--line);
  border-radius: 10px;
  padding: 12px 16px;
  font-size: 12px;
  margin-bottom: 8px;
}
.home-notices summary {
  cursor: pointer;
}
.home-notices p {
  white-space: pre-wrap;
  margin-top: 8px;
  color: var(--muted);
}
@media (min-width: 1500px) {
  .home-hero {
    min-height: 500px;
  }
  .hero-video {
    height: 365px;
  }
  .hero-image {
    height: 300px;
  }
}
@media (max-width: 1100px) {
  .home-hero {
    gap: 15px;
  }
  .hero-video {
    height: 270px;
  }
  .hero-image {
    height: 230px;
  }
  .hero-copy h1 {
    font-size: 40px;
  }
  .inspiration-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
@media (max-width: 760px) {
  .home-hero {
    grid-template-columns: 1fr;
    padding-top: 8px;
    gap: 26px;
  }
  .hero-copy h1 {
    font-size: 44px;
    letter-spacing: -1.8px;
  }
  .hero-description {
    max-width: 480px;
  }
  .hero-previews {
    min-height: 270px;
    max-width: 480px;
    width: 100%;
    margin: auto;
  }
  .hero-video {
    height: 255px;
  }
  .hero-image {
    height: 220px;
  }
  .section-heading h2 {
    font-size: 21px;
  }
  .inspiration-grid {
    gap: 14px;
  }
  .view-all {
    display: none;
  }
  .preview-label {
    bottom: -14px;
  }
  .inspiration-card > p {
    font-size: 12px;
  }
}
</style>
