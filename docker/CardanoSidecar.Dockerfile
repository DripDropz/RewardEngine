FROM node:20-alpine
MAINTAINER Latheesan Kanesamoorthy <latheesan87@gmail.com>

WORKDIR /app

RUN npm install -g nodemon

CMD ["npm", "run", "dev"]

EXPOSE 3000
