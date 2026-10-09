FROM node:24-alpine AS base

WORKDIR /app

FROM base AS dev

ENV NODE_ENV=development \
    NUXT_HOST=0.0.0.0

CMD ["sh", "-c", "npm ci && npm run dev -- --host 0.0.0.0"]

FROM base AS build

ENV NODE_ENV=production
ARG NUXT_APP_BASE_URL=/
ARG NUXT_PUBLIC_API_BASE_URL=/api
ENV NUXT_APP_BASE_URL=${NUXT_APP_BASE_URL}
ENV NUXT_PUBLIC_API_BASE_URL=${NUXT_PUBLIC_API_BASE_URL}

COPY nuxt/package.json nuxt/package-lock.json ./
RUN npm ci

COPY nuxt/ ./
RUN npm run build

FROM node:24-alpine AS production

WORKDIR /app

ENV NODE_ENV=production \
    HOST=0.0.0.0 \
    PORT=3000

COPY --from=build /app/.output ./.output

EXPOSE 3000

CMD ["node", ".output/server/index.mjs"]
