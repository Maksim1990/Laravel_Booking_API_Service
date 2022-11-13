
# Build image for K8s on DigitalOcean
#### docker build -t maksim1990/dashboard-api -f ./docker/php/Dockerfile.k8s .
### docker push maksim1990/dashboard-api

##### Connect to MongoDb via mongosh locally 
##### mongosh "mongodb://127.0.0.1:27017/?directConnection=true&serverSelectionTimeoutMS=2000" --username root --authenticationDatabase admin

### Connect to Mongo DB in Atlas
### mongosh "mongodb+srv://the-wug-main.q47bd.mongodb.net/myFirstDatabase" --username wug_admin

# Build image for K8s
#### aws ecr-public get-login-password --region us-east-1 | docker login --username AWS --password-stdin public.ecr.aws/d3v8g7l2
#### docker build -t dashboard-api -f ./docker/php/Dockerfile.k8s .
#### docker tag dashboard-api:latest public.ecr.aws/d3v8g7l2/dashboard-api:latest
#### docker push public.ecr.aws/d3v8g7l2/dashboard-api:latest


### Connect to EKS cluster
####  aws eks --region eu-central-1 update-kubeconfig --name eks-webmastery-cluster

eksctl create cluster --name eks-webmastery-cluster --region eu-central-1 --version 1.21 --fargate


## K8s
### Get info about cluster and get External IP
#### kubectl get nodes -o yaml 

#### kubectl scale --replicas=2 deployment dashboard-api
