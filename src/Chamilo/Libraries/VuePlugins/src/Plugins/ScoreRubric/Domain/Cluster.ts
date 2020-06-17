import {Element} from "@/Plugins/ScoreRubric/Domain/Rubric";
import Category, {CategoryJsonObject} from "@/Plugins/ScoreRubric/Domain/Category";
import Criterium, {CriteriumJsonObject} from "@/Plugins/ScoreRubric/Domain/Criterium";
import Container from "@/Plugins/ScoreRubric/Domain/Container";

export interface ClusterJsonObject {
    title: string;
    categories: CategoryJsonObject[];
    criteria: CriteriumJsonObject[];

}

export default class Cluster extends Container {
    public collapsed: boolean = false;

    public getScore(): number {
        return 0;
    }

    public toggleCollapsed() { //todo view state?
        this.collapsed = !this.collapsed;
    }

    public addCategory(category: Category): void {
        super.addChild(category);
    }

    public addCriterium(criterium: Criterium): void {
        super.addChild(criterium);
    }

    public removeCriterium(criterium: Criterium): void {
        super.removeChild(criterium);
    }

    public removeCategory(category:Category) {
        super.removeChild(category);
    }

    get criteria():Criterium[] {
        return this.children.filter(child => (child instanceof Criterium)) as Criterium[];
    }

    get categories():Category[] {
        return this.children.filter(child => (child instanceof Category)) as Category[];
    }

    get clusters():Cluster[] {
        return this.children as Cluster[]; //invariant garded at addChild
    }

    toJSON(): ClusterJsonObject {
        return {
            title: this.title,
            categories: this.children.filter(child => (child instanceof Category)).map((category) => (category as Category).toJSON()),//todo typeguards?
            criteria: this.children.filter(child => (child instanceof Criterium)).map((criterium) => (criterium as Criterium).toJSON())
        }
    }

    static fromJSON(cluster: string | ClusterJsonObject): Cluster {
        let clusterObject: ClusterJsonObject;
        if (typeof cluster === 'string') {
            clusterObject = JSON.parse(cluster);
        } else {
            clusterObject = cluster;
        }

        let newCluster = new Cluster(
            clusterObject.title
        );

        clusterObject.categories
            .map(categoryJsonObject => Category.fromJSON(categoryJsonObject))
            .forEach(category => newCluster.addCategory(category));
        clusterObject.criteria
            .map(criteriumObject => Criterium.fromJSON(criteriumObject))
            .forEach(criterium => newCluster.addCriterium(criterium));

        return newCluster;
    }
}